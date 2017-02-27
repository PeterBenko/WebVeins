/* This file is part of the Palabos library.
 *
 * Copyright (C) 2011-2015 FlowKit Sarl
 * Route d'Oron 2
 * 1010 Lausanne, Switzerland
 * E-mail contact: contact@flowkit.com
 *
 * The most recent release of Palabos can be downloaded at 
 * <http://www.palabos.org/>
 *
 * The library Palabos is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/** \file
  * This code solves the steady flow inside an aneurysm. It introduces several
  * new concepts like Guo off lattice boundary conditions, reading of
  * surface geometry STL files, smooth grid refinement and voxelization.
  * Make sure to unpack the file aneurysm.stl.tgz before running the
  * simulation.
  **/
#include <stdexcept>
#include <csignal>

#include "palabos3D.h"
#include "palabos3D.hh"

using namespace plb;
using namespace std;

typedef double T;
typedef Array<T,3> Velocity;
#define DESCRIPTOR descriptors::D3Q19Descriptor

plint extraLayer      = 0;  // Make the bounding box larger; for visualization purposes
                            //   only. For the simulation, it is OK to have extraLayer=0.
const plint blockSize = 20; // Zero means: no sparse representation.
const plint envelopeWidth = 1;  // For standard BGK dynamics.
const plint extendedEnvelopeWidth = 2;  // Because the Guo off lattice boundary condition
                                        //   needs 2-cell neighbor access.

bool performOutput = false;
bool doImages = false;
bool useAllDirections = false;
bool useRegularizedWall = false;
bool useIncompressible = false;
bool poiseuilleInlet = false;
bool convectiveScaling = false;

enum SliceDirection { 
    XYSliceDirection, 
    XZSliceDirection, 
    YZSliceDirection 
};

SliceDirection sliceDirection;
Array<T,3> slicePosition;


T kinematicViscosity       = 0.;
T averageInletVelocity     = 0.;
plint referenceResolution  = 0;
T nuLB                     = 0.;
T fluidDensity             = 0.;
T volume                   = 0.;
T userDefinedInletDiameter = 0.;

plint referenceDirection = 0;
plint openingSortDirection = 0;

T simTime = 0;
plint startLevel = 0;
plint maxLevel   = 0;
T epsilon = 0.;

TriangleSet<T>* triangleSet = 0;
T currentTime = 0;

// Structure which defines an ``opening''. The surface geometry of the aneurysm,
//   as given by the user in the form of an STL file, contains holes, which in 
//   the specific simulation represent inlets and outlets.
template<typename T>
struct Opening {
    bool inlet;
    Array<T,3> center;
    T innerRadius;
};

std::vector<Opening<T> > openings;

void iniLattice( MultiBlockLattice3D<T,DESCRIPTOR>& lattice,
                 VoxelizedDomain3D<T>& voxelizedDomain )
{
    // Switch all remaining outer cells to no-dynamics, except the outer
    //   boundary layer, and keep the rest as BGKdynamics.
    defineDynamics(lattice, voxelizedDomain.getVoxelMatrix(), lattice.getBoundingBox(),
                   new NoDynamics<T,DESCRIPTOR>, voxelFlag::outside);
    initializeAtEquilibrium(lattice, lattice.getBoundingBox(), (T) 1., Array<T,3>((T) 0.,(T) 0.,(T) 0.));
    lattice.initialize();
}

// This function assigns proper boundary conditions to the openings of the surface geometry
//   of the aneurysm. Which opening is inlet and which is outlet is defined by the user in
//   the input XML file. For the inlet, there is a choice between a Poiseuille velocity
//   profile and a simple plug velocity profile. At the outlets a Neumann boundary
//   condition with constant pressure is prescribed.
void setOpenings (
    std::vector<BoundaryProfile3D<T,Velocity>*>& inletOutlets,
    TriangleBoundary3D<T>& boundary, T uLB, T dx, T dt )
{
    for (pluint i=0; i<openings.size(); ++i) {
        Opening<T>& opening = openings[i];
        opening.center = computeBaryCenter (
                boundary.getMesh(),
                boundary.getInletOutlet(openingSortDirection)[i] );
        opening.innerRadius = computeInnerRadius (
                boundary.getMesh(),
                boundary.getInletOutlet(openingSortDirection)[i] );

        if (opening.inlet) {
            if (poiseuilleInlet) {
                inletOutlets.push_back (
                        new PoiseuilleProfile3D<T>(uLB) );
            }
            else {
                inletOutlets.push_back (
                        new VelocityPlugProfile3D<T>(uLB) );
            }
        }
        else {
            inletOutlets.push_back (
                    new DensityNeumannBoundaryProfile3D<T> );
        }
    }
}

void writeSlice (
         OffLatticeBoundaryCondition3D<T,DESCRIPTOR,Velocity>& boundaryCondition,
         Box3D const& imageDomain, Box3D const& vtkDomain, std::string fname, Array<T,3> location, T dx, T dt )
{
    //VtkImageOutput3D<T> vtkOut(fname, dx, location);
    //vtkOut.writeData<float>(*boundaryCondition.computePressure(vtkDomain), "p", util::sqr(dx/dt)*fluidDensity);
    //vtkOut.writeData<float>(*boundaryCondition.computeVelocityNorm(vtkDomain), "u", dx/dt);
    //vtkOut.writeData<float>(*copyConvert<int,T>(*extractSubDomain(boundaryCondition.getVoxelizedDomain().getVoxelMatrix(),vtkDomain)), "voxel", 1.);

    ImageWriter<T> imageWriter("leeloo");
    imageWriter.writeScaledPpm(fname, *boundaryCondition.computeVelocityNorm(imageDomain));
}

// This function produces images at predefined yz, xz and xy planes. The coordinates of the planes are given
//   in physical coordinates, and the output variables are velocity, vorticity and pressure.
void writeImages (
         OffLatticeBoundaryCondition3D<T,DESCRIPTOR,Velocity>& boundaryCondition, plint level, plint iteration,Array<T,3> location, T dx, T dt )
{
    plint nx = boundaryCondition.getLattice().getNx();
    plint ny = boundaryCondition.getLattice().getNy();
    plint nz = boundaryCondition.getLattice().getNz();

    plint x_pos = util::roundToInt((nx-1) * slicePosition[0]);
    plint y_pos = util::roundToInt((nx-1) * slicePosition[1]);    
    plint z_pos = util::roundToInt((nz-1) * slicePosition[2]);

    plint vtk_slice_thickness = 6;

    Box3D yz_imageDomain (
            x_pos, x_pos,
            0, ny-1, 
            0, nz-1 );

    Box3D xz_imageDomain (
            0, nx-1,
            y_pos, y_pos,
            0, nz-1 );

    Box3D xy_imageDomain (
            0, nx-1, 
            0, ny-1,
            z_pos, z_pos);

    Box3D yz_vtkDomain (
            x_pos - vtk_slice_thickness/2, x_pos + vtk_slice_thickness/2,
            0, ny-1, 
            0, nz-1 );
    Box3D xz_vtkDomain (
            0, nx-1,
            y_pos - vtk_slice_thickness/2, y_pos + vtk_slice_thickness/2,
            0, nz-1 );
    Box3D xy_vtkDomain (
            0, nx-1, 
            0, ny-1,
            z_pos - vtk_slice_thickness/2, z_pos + vtk_slice_thickness/2 );

    writeSlice(boundaryCondition, xy_imageDomain, xy_vtkDomain, "xy_"+util::val2str(level)+"_"+util::val2str(iteration), location, dx, dt);
    writeSlice(boundaryCondition, xz_imageDomain, xz_vtkDomain, "xz_"+util::val2str(level)+"_"+util::val2str(iteration), location, dx, dt);
    writeSlice(boundaryCondition, yz_imageDomain, yz_vtkDomain, "yz_"+util::val2str(level)+"_"+util::val2str(iteration), location, dx, dt);

    Box3D full_vtkDomain (
            0, nx-1, 
            0, ny-1,
            0, nz-1);

    VtkImageOutput3D<T> vtkOut("full_"+util::val2str(iteration), dx, location);
    vtkOut.writeData<float>(*boundaryCondition.computePressure(full_vtkDomain), "p", util::sqr(dx/dt)*fluidDensity);
    vtkOut.writeData<float>(*boundaryCondition.computeVelocityNorm(full_vtkDomain), "u", dx/dt);
    vtkOut.writeData<float>(*copyConvert<int,T>(*extractSubDomain(boundaryCondition.getVoxelizedDomain().getVoxelMatrix(),full_vtkDomain)), "voxel", 1.);
}


// This is the function that prepares and performs the actual simulation.
std::auto_ptr<MultiBlockLattice3D<T,DESCRIPTOR> > run (
        plint level, MultiBlockLattice3D<T,DESCRIPTOR>* iniVal=0 )
{
    plint margin = 3; // Extra margin of allocated cells around the obstacle. 
    plint borderWidth = 1; // Because the Guo boundary condition acts in a one-cell layer.
                           // Requirement: margin>=borderWidth.

    // The resolution is doubled at each coordinate direction with the increase of the
    //   resolution level by one. The parameter ``referenceResolution'' is by definition
    //   the resolution at grid refinement level 0.
    plint resolution = referenceResolution * util::twoToThePower(level);

    // The next few lines of code are typical. They transform the surface geometry of the
    //   aneurysm given by the user to more efficient data structures that are internally
    //   used by palabos. The TriangleBoundary3D structure will be later used to assign
    //   proper boundary conditions.
    DEFscaledMesh<T>* defMesh =
        new DEFscaledMesh<T>(*triangleSet, resolution, referenceDirection, margin, extraLayer);
    TriangleBoundary3D<T> boundary(*defMesh);
    delete defMesh;
    boundary.getMesh().inflate();

    // When convective scaling is used (relationship of dt with respect to dx as the grid is
    //   refined) the value of the kinematic viscosity must be also properly adjusted.
    T nuLB_ = nuLB;
    if (convectiveScaling) {
        nuLB_ = nuLB * util::twoToThePower(level);
    }
    T dx = boundary.getDx();
    T dt = nuLB_ / kinematicViscosity *dx*dx;
    T uAveLB = averageInletVelocity *dt/dx;
    T omega = 1./(3.*nuLB_+0.5);
    Array<T,3> location(boundary.getPhysicalLocation());


    pcout << "uLB=" << uAveLB << std::endl;
    pcout << "nuLB=" << nuLB_ << std::endl;
    pcout << "tau=" << 1./omega << std::endl;
    if (performOutput) {
        pcout << "dx=" << dx << std::endl;
        pcout << "dt=" << dt << std::endl;
    }

    // Next the inlets and outlets are identified (according to what the user has specified)
    //   in the input XML file, and proper boundary conditions are assigned.
    std::vector<BoundaryProfile3D<T,Velocity>*> inletOutlets;
    setOpenings(inletOutlets, boundary, uAveLB, dx, dt);
    Array<T,3> inletCenter(0.0, 0.0, 0.0);
    for (pluint i=0; i<openings.size(); ++i) {
        if (openings[i].inlet) {
            pcout << "Inner radius of inlet " << i << " : "
                  << openings[i].innerRadius << " lattice nodes" << std::endl;
            inletCenter=openings[i].center;
        }
    }
    T inletZpos = util::roundToInt(inletCenter[2])+1;
    BoundaryProfiles3D<T,Velocity> profiles;
    profiles.defineInletOutletTags(boundary, openingSortDirection);
    profiles.setInletOutlet(inletOutlets);

    // The aneurysm simulation is an interior (as opposed to exterior) flow problem. For
    //   this reason, the lattice nodes that lay inside the computational domain must
    //   be identified and distinguished from the ones that lay outside of it. This is
    //   handled by the following voxelization process.
    const int flowType = voxelFlag::inside;
    VoxelizedDomain3D<T> voxelizedDomain (
            boundary, flowType, extraLayer, borderWidth, extendedEnvelopeWidth, blockSize );
    if (performOutput) {
        pcout << getMultiBlockInfo(voxelizedDomain.getVoxelMatrix()) << std::endl;
    }

    MultiScalarField3D<int> flagMatrix((MultiBlock3D&)voxelizedDomain.getVoxelMatrix());
    setToConstant(flagMatrix, voxelizedDomain.getVoxelMatrix(),
                  voxelFlag::inside, flagMatrix.getBoundingBox(), 1);
    setToConstant(flagMatrix, voxelizedDomain.getVoxelMatrix(),
                  voxelFlag::innerBorder, flagMatrix.getBoundingBox(), 1);
    pcout << "Number of fluid cells: " << computeSum(flagMatrix) << std::endl;

    Dynamics<T,DESCRIPTOR>* dynamics = 0;
    if (useIncompressible) {
        dynamics = new IncBGKdynamics<T,DESCRIPTOR>(omega); // In this model velocity equals momentum.
    }
    else {
        dynamics = new BGKdynamics<T,DESCRIPTOR>(omega); // In this model velocity equals momentum
                                                         //   divided by density.
    }
    std::auto_ptr<MultiBlockLattice3D<T,DESCRIPTOR> > lattice 
        = generateMultiBlockLattice<T,DESCRIPTOR> (
                voxelizedDomain.getVoxelMatrix(), envelopeWidth, dynamics );
    lattice->toggleInternalStatistics(false);

    // The next piece of code is put for efficiency reasons at communications in parallel runs.
    //   The efficiency advantage comes essentially because the density and velocity are
    //   written in different fields.
    std::vector<MultiBlock3D*> rhoBarJarg;
    plint numScalars = 4;
    MultiNTensorField3D<T>* rhoBarJfield =
          generateMultiNTensorField3D<T>(*lattice, extendedEnvelopeWidth, numScalars);
    rhoBarJfield->toggleInternalStatistics(false);
    rhoBarJarg.push_back(rhoBarJfield);
    plint processorLevel=0;
    integrateProcessingFunctional (
            new PackedRhoBarJfunctional3D<T,DESCRIPTOR>(),
            lattice->getBoundingBox(), *lattice, *rhoBarJfield, processorLevel );

    // The Guo off lattice boundary condition is set up.
    GuoOffLatticeModel3D<T,DESCRIPTOR>* model =
            new GuoOffLatticeModel3D<T,DESCRIPTOR> (
                new TriangleFlowShape3D<T,Array<T,3> > (
                    voxelizedDomain.getBoundary(), profiles),
                flowType, useAllDirections );
    model->setVelIsJ(useIncompressible); // When the incompressible BGK model is used, velocity equals momentum.
    model->selectUseRegularizedModel(useRegularizedWall);
    model->selectComputeStat(false);
    OffLatticeBoundaryCondition3D<T,DESCRIPTOR,Velocity> boundaryCondition (
            model, voxelizedDomain, *lattice);
    boundaryCondition.insert(rhoBarJarg);

    iniLattice(*lattice, voxelizedDomain);
    if(iniVal) {
        Box3D toDomain(lattice->getBoundingBox());
        Box3D fromDomain(toDomain.shift(margin,margin,margin)); // During rescaling, the margin doubled in size,
                                                                //   an effect which is cancelled here through a shift.
        copy(*iniVal, fromDomain, *lattice, toDomain, modif::staticVariables);
        computePackedRhoBarJ(*lattice, *rhoBarJfield, lattice->getBoundingBox());
        boundaryCondition.apply(rhoBarJarg);
    }

    // The ValueTracer is needed to check when a chosen quantity (in our case the average energy)
    //   has converged, so to conclude that steady state has been reached for the specific grid
    //   refinement level and stop the simulation.
    plint convergenceIter=20;
    util::ValueTracer<T> velocityTracer(0.05*convergenceIter, resolution, epsilon);
    global::timer("iteration").restart();
    plint i = util::roundToInt(currentTime/dt);
    plint out = 0;
    const T out_step = 0.067;
    lattice->resetTime(i);

    // Collision and streaming iterations.
    while(!velocityTracer.hasConverged() && currentTime<simTime)
    {

        if (currentTime > out*out_step) {
            pcout << "Writing VTK file at time "
                  << currentTime << endl;

            writeImages(boundaryCondition, level, out, location, dx, dt);

            ++out;
        }

        if (i%200==0 && performOutput) {
            pcout << "T= " << currentTime << "; "
                  << "Average energy: "
                  << boundaryCondition.computeAverageEnergy()*util::sqr(dx/dt) << std::endl;
        }
        if (i%convergenceIter==0) {
            velocityTracer.takeValue(computeAverageEnergy(*lattice));
        }

        lattice->collideAndStream();

        ++i;
        currentTime = i*dt;
    }

    delete rhoBarJfield;

    Box3D measureBox(lattice->getBoundingBox());
    measureBox.z0=measureBox.z1=(plint)inletZpos;
    T inletPressure = DESCRIPTOR<T>::cs2*(boundaryCondition.computeAverageDensity(measureBox)-1.);

    // Image output.
    if (doImages) {

        writeImages(boundaryCondition, level, i, location, dx, dt);

        std::vector<std::string> scalarNames;
        scalarNames.push_back("pressure");
        scalarNames.push_back("wss");
        std::vector<T> scalarFactor;
        scalarFactor.push_back(util::sqr(dx/dt)*fluidDensity);
        scalarFactor.push_back(util::sqr(dx/dt)*fluidDensity);

        std::vector<std::string> vectorNames;
        vectorNames.push_back("force");
        std::vector<T> vectorFactor;
        vectorFactor.push_back(util::sqr(dx/dt)*fluidDensity);

        bool dynamicMesh = false;
        writeSurfaceVTK (
                boundary,
                *computeSurfaceForce( boundary, voxelizedDomain, *lattice, model->velIsJ(), dynamicMesh ),
                scalarNames, vectorNames, "surface_"+util::val2str(level)+".vtk", dynamicMesh, 0,
                scalarFactor, vectorFactor );
    }

    T averageEnergy = boundaryCondition.computeAverageEnergy()*util::sqr(dx/dt);
    T rmsVorticity  = boundaryCondition.computeRMSvorticity()/dt;
    T pressureDrop = inletPressure*util::sqr(dx/dt)*fluidDensity;
    T inletAverageVel = boundaryCondition.computeAverageVelocityComponent(measureBox,2)*dx/dt;

    if (performOutput) {
        pcout << "Average energy: " << averageEnergy << std::endl;
        pcout << "Total energy: " << averageEnergy*volume << std::endl;
        pcout << "RMS vorticity * volume * 0.5: " << rmsVorticity*0.5*volume << std::endl;
        pcout << "Pressure drop: " << pressureDrop << std::endl;
        pcout << "Average velocity through inlet section: " << inletAverageVel << std::endl;
        pcout << "Number of iterations: " << i << std::endl;
    }
    pcout << "Elapsed time: " << global::timer("iteration").stop() << std::endl;
    pcout << "Total elapsed time: " << global::timer("global").getTime() << std::endl;

    if (performOutput) {
        pcout << "Description: "
              << "Tot. energy, pressure-drop, tot. enstrophy,"
              << "  vel1, vort1, pres1,  vel2, vort2, pres2,"
              << "  vel3, vort3, pres3,  vel4, vort4, pres4,"
              << "  vel5, vort5, pres5,  vel6, vort6, pres6" << std::endl;
        pcout << "All data: ";
    }
    pcout << averageEnergy*volume << ", " << pressureDrop << ", " << rmsVorticity*volume*0.5 << ", ";

    return lattice;
}

// Read the user input XML file provided at the command-line.
void readParameters(XMLreader const& document)
{
    std::string meshFileName;
    std::vector<std::string> openingType;
    document["geometry"]["mesh"].read(meshFileName);
    document["geometry"]["inletDiameter"].read(userDefinedInletDiameter);
    document["geometry"]["averageInletVelocity"].read(averageInletVelocity);
    document["geometry"]["openings"]["sortDirection"].read(openingSortDirection);
    document["geometry"]["openings"]["type"].read(openingType);

    document["fluid"]["kinematicViscosity"].read(kinematicViscosity);
    document["fluid"]["density"].read(fluidDensity);
    document["fluid"]["volume"].read(volume);

    document["numerics"]["referenceDirection"].read(referenceDirection);
    document["numerics"]["referenceResolution"].read(referenceResolution);
    document["numerics"]["nuLB"].read(nuLB);

    document["simulation"]["simTime"].read(simTime);
    document["simulation"]["maxLevel"].read(maxLevel);
    document["simulation"]["epsilon"].read(epsilon);

    document["simulation"]["performOutput"].read(performOutput);
    document["simulation"]["doImages"].read(doImages);
    document["simulation"]["useAllDirections"].read(useAllDirections);
    document["simulation"]["useRegularizedWall"].read(useRegularizedWall);
    document["simulation"]["useIncompressible"].read(useIncompressible);
    document["simulation"]["poiseuilleInlet"].read(poiseuilleInlet);
    document["simulation"]["convectiveScaling"].read(convectiveScaling);

    string directionString;
    document["simulation"]["sliceDirection"].read(directionString);
    if (directionString == "XY") 
    {
        sliceDirection = XYSliceDirection;
    } 
    else if (directionString == "XZ")
    {
        sliceDirection = XZSliceDirection;
    }
    else if (directionString == "YZ")
    {
        sliceDirection = YZSliceDirection;
    }
    else 
    {
        pcout << directionString << " is not a valid slice plane definition";
        throw std::invalid_argument(directionString + " is not a valid slice plane definition");
    }
    
    T slice_x, slice_y, slice_z;
    document["simulation"]["slicePosition"]["X"].read(slice_x);
    document["simulation"]["slicePosition"]["Y"].read(slice_y);
    document["simulation"]["slicePosition"]["Z"].read(slice_z);
    slicePosition = Array<T,3>(slice_x, slice_y, slice_z);

    // At this part, the surface geometry of the aneurysm (as given by the user in
    //   the form of an ASCII or binary STL file) is read into a data structure
    //   comprised by a set of triangles. The DBL constant means that double
    //   precision accuracy will be used (generally the recommended choice).
    triangleSet = new TriangleSet<T>(meshFileName, DBL);
    pcout << "Reynolds number, based on provided inlet diameter: "
          << averageInletVelocity*userDefinedInletDiameter/kinematicViscosity
          << std::endl;
    plbIOError(openingSortDirection<0 || openingSortDirection>2,
               "Sort-direction of opening must be 0 (x), 1 (y), or 2 (z).");
    // The surface geometry, as provided by the STL file, must contain openings,
    //   namely inlets and outlets. On these openings, appropriate boundary conditions
    //   will be imposed by palabos. Which opening is inlet and which is outlet, is
    //   identified by the user in the input XML file.
    openings.resize(openingType.size());
    for (pluint i=0; i<openingType.size(); ++i) {
        std::string next_opening = util::tolower(openingType[i]);
        if (next_opening=="inlet") {
            openings[i].inlet = true;
        }
        else if (next_opening=="outlet") {
            openings[i].inlet = false;
        }
        else {
            plbIOError("Unknown opening type.");
        }
    }
}

void signalHandler( int signum ) {
   cout << "Interrupt received. Exiting.";
   exit(signum);  
}

int main(int argc, char* argv[])
{
    signal(SIGTERM, signalHandler);   

    plbInit(&argc, &argv);
    global::directories().setOutputDir("./");
    global::IOpolicy().activateParallelIO(false);

    string paramXmlFileName;
    try {
        global::argv(1).read(paramXmlFileName);
    }
    catch (PlbIOException& exception) {
        pcout << "Wrong parameters; the syntax is: " 
              << (std::string)global::argv(0) << " parameter-input-file.xml" << std::endl;
        return -1;
    }

    // Read the parameter XML input file. (Lots of comments are included there too).
    try {
        XMLreader document(paramXmlFileName);
        readParameters(paramXmlFileName);
    }
    catch (PlbIOException& exception) {
        pcout << "Error in input file " << paramXmlFileName
              << ": " << exception.what() << std::endl;
        return -1;
    }

    global::timer("global").start();
    plint iniLevel=0;
    std::auto_ptr<MultiBlockLattice3D<T,DESCRIPTOR> > iniConditionLattice(0);
    // This code incorporates the concept of smooth grid refinement until convergence is
    //   achieved. The word ``smooth'' indicates that as the refinement level increases
    //   by one, the whole grid doubles in each direction. When the grid is refined, both
    //   dx and dt have to change. Whether dt is changed as dx^2 (diffusive behavior)
    //   or as dx (convective behavior), is controlled by the input variable
    //   ``convectiveScaling'' (the recommended choice is not to use convective scaling).
    try {
        for (plint level=iniLevel; level<=maxLevel; ++level) {
            pcout << std::endl << "Running new simulation at level " << level << std::endl;
            std::auto_ptr<MultiBlockLattice3D<T,DESCRIPTOR> > convergedLattice (
                    run(level, iniConditionLattice.get()) );
            if (level != maxLevel) {
                plint dxScale = -1;
                plint dtScale = -2;
                if (convectiveScaling) {
                    dtScale = -1;
                }
                // The converged simulation of the previous grid level is used as the initial condition
                //   for the simulation at the next grid level (after appropriate interpolation has
                //   taken place).
                iniConditionLattice = std::auto_ptr<MultiBlockLattice3D<T,DESCRIPTOR> > (
                        refine(*convergedLattice, dxScale, dtScale, new BGKdynamics<T,DESCRIPTOR>(1.)) );
            }
        }
    }
    catch(PlbException& exception) {
        pcout << exception.what() << std::endl;
        return -1;
    }
}
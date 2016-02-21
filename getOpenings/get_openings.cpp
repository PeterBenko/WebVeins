#include "palabos3D.h"
#include "palabos3D.hh"

using namespace plb;
using namespace std;

void getOpenings (TriangleSet<double>* triangleSet)
{
    DEFscaledMesh<double>* defMesh = new DEFscaledMesh<double>(*triangleSet, 0, 0, 0, 0);
    TriangularSurfaceMesh<double> mesh = defMesh->getMesh();
    std::vector< std::vector< plint > > holes = mesh.detectHoles();
    for(unsigned int i = 0; i< holes.size() ; i++){
        for (unsigned int j=0; j<holes[i].size(); j++){
            plint vertIndex = holes[i][j];
            Array< double, 3 > const vertex = mesh.getVertex(vertIndex);
            cout << vertex[0] << " " << vertex[1] << " "<< vertex[2] << " " << std::endl;
        }
        cout << std::endl;
    }

    delete defMesh;
}

int main(int argc, char* argv[])
{
    plbInit(&argc, &argv);
    global::directories().setOutputDir("./");
    global::IOpolicy().activateParallelIO(false);

    string meshFilename;
    try {
        global::argv(1).read(meshFilename);
    }
    catch (PlbIOException& exception) {
        pcout << "Wrong parameters; the syntax is: "
        << (std::string)global::argv(0) << " mesh_filename" << std::endl;
        return -1;
    }

    std::string meshFileName;
    TriangleSet<double>* triangleSet = new TriangleSet<double>(meshFilename, DBL);
    getOpenings(triangleSet);
}


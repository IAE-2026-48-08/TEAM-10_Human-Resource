<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EmployeeController extends Controller
{
    #[OA\Get(
        path: '/api/v1/employees',
        summary: 'Get all employees',
        security: [['ApiKeyAuth' => []]],
        tags: ['Employees'],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => Employee::all(),
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ]);
    }

    #[OA\Get(
        path: '/api/v1/employees/{id}',
        summary: 'Get employee by ID',
        security: [['ApiKeyAuth' => []]],
        tags: ['Employees'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function show(int $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found',
                'errors' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $employee,
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ]);
    }

    #[OA\Post(
        path: '/api/v1/employees',
        summary: 'Create employee',
        security: [['ApiKeyAuth' => []]],
        tags: ['Employees'],
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function store(Request $request)
    {
        $employee = Employee::create([
            'nip' => $request->nip,
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'departemen' => $request->departemen,
            'gaji_pokok' => $request->gaji_pokok,
            'email' => $request->email
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created successfully',
            'data' => $employee,
            'meta' => [
                'service_name' => 'Data-Karyawan-Service',
                'api_version' => 'v1'
            ]
        ], 201);
    }
}
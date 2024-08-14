<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private $client;

    // Inject GuzzleHttp\Client via constructor
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:employees,email',
            'position' => 'required',
            'salary' => 'required|numeric',
            'token' => 'required',
        ]);

        $token = $request->input('token');
        $recaptchaResponse = $this->verifyRecaptcha($token);

        if (!$recaptchaResponse['success']) {
            return response()->json(['success' => false, 'message' => 'Invalid reCAPTCHA token'], 400);
        }

        $employee = Employee::create($request->except('token'));

        return response()->json($employee, 201);
    }

    private function verifyRecaptcha($token)
    {
        $secretKey = env('RECAPTCHA_SECRET_KEY');
        $response = $this->client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $secretKey,
                'response' => $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function index()
    {
        return Employee::all();
    }

    public function show($id)
    {
        return Employee::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:employees,email,'.$id,
            'position' => 'required',
            'salary' => 'required|numeric',
        ]);

        $employee = Employee::findOrFail($id);
        $employee->update($request->all());

        return response()->json($employee, 200);
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json(null, 204);
    }
}

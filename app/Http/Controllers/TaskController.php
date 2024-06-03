<?php

namespace App\Http\Controllers;
use Validator;
use App\Model\Task;
use Illuminate\Http\Request;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Response;

class TaskController extends Controller
{
    /**
     * Display a listing of all tasks.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        try {
            // Retrieve all tasks from the database
            $records = Task::all();
            // Return a JSON response using the TaskResource collection
            return TaskResource::collection($records);
        } catch (\Exception $e) {
            // Catch any exceptions that occur during the retrieval process
            // Return a JSON response with the error message and a 404 status code
            return response()->json(['error' => $e->getMessage()])->setStatusCode(404);
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $data=$request->all();
        $task = Task::create($data);
        return response()->json(['message'=>'Task created successfully'], 201);
    }
}

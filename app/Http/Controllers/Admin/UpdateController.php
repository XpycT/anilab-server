<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UpdateCreateRequest;
use App\Http\Requests\UpdateEditRequest;
use App\Update;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class UpdateController extends Controller
{
    protected $fields = [
        'version_code' => '',
        'version_name' => '',
        'content' => ''
    ];
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $updates = Update::all();

        return view('admin.update.index',compact('updates'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $data = [];
        foreach ($this->fields as $field => $default) {
            $data[$field] = old($field, $default);
        }
        return view('admin.update.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return UpdateCreateRequest
     */
    public function store(UpdateCreateRequest $request)
    {
        $update = new Update($request->all());
        $update->save();

        return redirect('admin/update')->withSuccess("The update '$update->version_code' was created.");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    public function getVersion(){
        $update = Update::all()->last();
        return $update;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $update = Update::findOrFail($id);
        $data = ['id' => $id];
        foreach (array_keys($this->fields) as $field) {
            $data[$field] = old($field, $update->$field);
        }

        return view('admin.update.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return UpdateEditRequest
     */
    public function update(UpdateEditRequest $request, $id)
    {
        $update = Update::findOrFail($id);
        foreach (array_keys($this->fields) as $field) {
            $update->$field = $request->get($field);
        }
        $update->save();

        return redirect()->action('Admin\UpdateController@edit', array($id))->withSuccess("Changes saved.");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $update = Update::findOrFail($id);
        $update->delete();

        return redirect('admin/update')
            ->withSuccess("The '$update->version_code' update has been deleted.");
    }
}

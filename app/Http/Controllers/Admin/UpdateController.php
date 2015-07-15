<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UpdateCreateRequest;
use App\Http\Requests\UpdateEditRequest;
use App\Update;
use File;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Input;
use Response;
use Storage;

class UpdateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $updates = Update::all();

        return view('admin.update.index', compact('updates'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $postData = Input::all();
        return view('admin.update.create', compact('postData'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return UpdateCreateRequest
     */
    public function store(UpdateCreateRequest $request)
    {
        $update = new Update($request->all());

        $file = $request->file('apk_file');
        if ($file) {
            $extension = $file->getClientOriginalExtension();
            Storage::disk('local')->put($file->getFilename() . '.' . $extension, File::get($file));
            $update->mime = $file->getClientMimeType();
            $update->original_filename = $file->getClientOriginalName();
            $update->filename = $file->getFilename() . '.' . $extension;
        }
        $update->save();

        return redirect('admin/update')->withSuccess("The update '$update->version_code' was created.");
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Get lates version of update
     *
     * @return mixed
     */
    public function getVersion()
    {
        $update = Update::all()->last();
        return $update;
    }

    /**
     * Get lates apk file
     *
     * @return mixed
     */
    public function getFile()
    {
        $update = Update::all()->last();
        //$file = Storage::disk('local')->get($update->filename);
        $file = storage_path('app').DIRECTORY_SEPARATOR.$update->filename;
        return response()->download($file,'anilab-latest.apk',['Content-Type'=>$update->mime]);
        //return response($file,200,['Content-Type'=>$update->mime,'Content-Disposition'=>'inline filename="Hate you"']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        $update = Update::findOrFail($id);
        return view('admin.update.edit', compact('update'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @param  int $id
     * @return UpdateEditRequest
     */
    public function update(UpdateEditRequest $request, $id)
    {
        $update = Update::findOrFail($id);
        $update->update($request->all());
        $file = $request->file('apk_file');
        if ($file) {
            //remove old
            Storage::disk('local')->delete($update->filename);
            // upload new
            $extension = $file->getClientOriginalExtension();
            Storage::disk('local')->put($file->getFilename() . '.' . $extension, File::get($file));
            $update->mime = $file->getClientMimeType();
            $update->original_filename = $file->getClientOriginalName();
            $update->filename = $file->getFilename() . '.' . $extension;
        }
        $update->save();

        return redirect()->action('Admin\UpdateController@edit', array($id))->withSuccess("Changes saved.");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $update = Update::findOrFail($id);
        Storage::disk('local')->delete($update->filename);
        $update->delete();

        return redirect('admin/update')
            ->withSuccess("The '$update->version_code' update has been deleted.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TokenCreateRequest;
use App\Http\Requests\TokenUpdateRequest;
use App\Token;
use Auth;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class TokenController extends Controller
{
    protected $fields = [
        'name' => '',
        'description' => '',
        'package_id' => ''
    ];
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $tokens = Token::all();

        return view('admin.token.index',compact('tokens'));
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
        return view('admin.token.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(TokenCreateRequest $request)
    {
        $token = new Token($request->all());
        Auth::user()->tokens()->save($token);

        return redirect('admin/token')->withSuccess("The token '$token->package_id' was created.");
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $token = Token::findOrFail($id);
        $data = ['id' => $id];
        foreach (array_keys($this->fields) as $field) {
            $data[$field] = old($field, $token->$field);
        }

        return view('admin.token.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(TokenUpdateRequest $request, $id)
    {
        $token = Auth::user()->tokens()->findOrFail($id);
        //$token = Token::findOrFail($id);
        foreach (array_keys($this->fields) as $field) {
            $token->$field = $request->get($field);
        }
        //$token->save();

        Auth::user()->tokens()->save($token);

        return redirect()->action('Admin\TokenController@edit', array($id))->withSuccess("Changes saved.");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $token = Auth::user()->tokens()->findOrFail($id);
        $token->delete();

        return redirect('admin/token')
            ->withSuccess("The '$token->package_id' app has been deleted.");
    }
}

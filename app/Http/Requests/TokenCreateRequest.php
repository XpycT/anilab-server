<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class TokenCreateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'package_id' => 'required|unique:tokens|max:150',
            'name' => 'required|max:255'
        ];
    }
}

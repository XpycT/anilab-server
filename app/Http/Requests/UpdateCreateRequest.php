<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UpdateCreateRequest extends Request
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
            'version_code' => 'required|numeric|unique:updates|max:150',
            'version_name' => 'required|max:20',
            'content' => 'required',
        ];
    }
}

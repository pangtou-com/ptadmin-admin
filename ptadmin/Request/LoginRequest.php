<?php

declare(strict_types=1);
/**
 * Author: Zane
 * Email: 873934580@qq.com
 * Date: 2023/5/24.
 */

namespace PTAdmin\Admin\Request;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required',
            'password' => 'required',
        ];
    }
}

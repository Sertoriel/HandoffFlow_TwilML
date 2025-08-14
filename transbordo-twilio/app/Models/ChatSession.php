<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    //
    protected $fillable = [
        'customer_id',
        'initial_message',
        'status',
        'operator_id'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}

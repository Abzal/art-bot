<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{

protected $fillable = [
        'subject',
        'contact_phone',
        'contact_fio',
        'ticket',
         'status_id',
                'channel',
                'created_at',
    ];

}

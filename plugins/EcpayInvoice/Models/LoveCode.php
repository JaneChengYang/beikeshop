<?php

namespace Plugin\EcpayInvoice\Models;

use Illuminate\Database\Eloquent\Model;

class LoveCode extends Model
{
    protected $table = 'ecpay_love_codes';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'short_name',
        'love_code',
        'tax_id',
        'city',
    ];
}

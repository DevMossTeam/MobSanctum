<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = ['device_token','device_type'];
}

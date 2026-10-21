<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A message sent through the contact form on the public site. */
class ContactRequest extends Model
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'];

    protected $guarded = [];

    protected $casts = ['acepta_privacidad' => 'boolean', 'fecha_aceptacion' => 'datetime'];
}

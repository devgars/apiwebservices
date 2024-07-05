<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchDetailsTracking extends Model
{
    // Define la tabla de base de datos
    protected $table = 'dispatch_details_tracking';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'dispatch_tracking_id',
        'personal_code',
        'packaging_group',
        'transit_status_id',
        'description_status',
        'description_status_web',
        'observations',
        'registration_status',
        'program',
        'username',
        'work',
        'date_of_work',
        'work_time',
        'status',
        'sincronizado_as400',
    ];


    //==============================
    // RELACIONES
    //==============================


    //RELACION CON  EL TRACKING
    public function tracking()
    {
        return $this->belongsTo('App\Models\MMTrack\DispatchTracking', 'dispatch_tracking_id');
    } 

    //relacion con el estatus de transito
    public function transit()
    {
        return $this->belongsTo('App\Models\MMTrack\Transit','transit_status_id');
    }

    //====================
    // SCOPES
    //====================
    //tracking validos
    public function scopeValidos($query)
    {
        return $query->whereIn('transit_status_id',[3,4,5,6,7,8,9,32,31,2,33]);
    } 

}

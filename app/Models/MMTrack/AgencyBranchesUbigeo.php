<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyBranchesUbigeo extends Model
{

    // Define la tabla de base de datos
    protected $table = 'agency_branches_departemen';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'agency_branches_id',
        'departemen_id',
    ];

    //=============================
    //RELACIONES
    //=============================

    public function agencyBranches() {
        return $this->belongsToMany('App\Models\MMTrack\AgencyBranches','agency_branches_id','departemen_id');

    }

}

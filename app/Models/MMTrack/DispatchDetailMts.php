<?php

namespace App\Models\mmtrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchDetailMts extends Model
{
    protected $table = 'dispatch_detail_mts';

    protected $fillable = [
        'dispatch_id',
        'cecodcia',
        'cecodsuc',
        'cenroped',
        'cenropdc',
        'ceitem01',
        'cecodalm',
        'cecodlin',
        'cecodart',
        'cecodori',
        'cecodmar',
        'cedscart',
        'cecandsp',
        'cecandev',
        'ceimppre',
        'cestslon',
        'cedctlin',
        'cedctadi',
        'ceprcimp',
        'cestsprm',
        'cestsite',
        'cests',
        'ceusr',
        'cejob',
        'cejdt',
        'cejtm'
    ];

    public function despacho()
    {

        return $this->hasOne('App\Models\MMTrack\Dispatch', 'dispatch_id', 'id');
    }

    //RELACION CON  EL pedido
    public function pedido()
    {
        return $this->belongsTo('App\Models\MMTrack\Dispatch', 'dispatch_id');
    } 
    
}

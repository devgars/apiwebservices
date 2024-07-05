<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UsersExport implements FromCollection, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public $users;
    public function __construct($users)
    {
        $this->users =  $users;
    }
    public function collection()
    {   
        $data =DB::table('users')
        ->select("*")
        ->where('id', $this->users["id"])
        ->get();
        //$user =  User::all();
        return $data;
    }
}

<?php

namespace Bantenprov\Sekolah\Http\Controllers;

/* Require */
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Bantenprov\Sekolah\Facades\SekolahFacade;

/* Models */
use Bantenprov\Sekolah\Models\Bantenprov\Sekolah\Sekolah;
use Bantenprov\Sekolah\Models\Bantenprov\Sekolah\JenisSekolah;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;
use Bantenprov\Zona\Models\Bantenprov\Zona\MasterZona;
use App\User;

/* Etc */
use Validator;
use Auth;

/**
 * The SekolahController class.
 *
 * @package Bantenprov\Sekolah
 * @author  bantenprov <developer.bantenprov@gmail.com>
 */
class SekolahController extends Controller
{
    protected $sekolah;
    protected $jenis_sekolah;
    protected $master_zona;
    protected $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->sekolah          = new Sekolah;
        $this->jenis_sekolah    = new JenisSekolah;
        $this->province         = new Province;
        $this->city             = new City;
        $this->district         = new District;
        $this->village          = new Village;
        $this->master_zona      = new MasterZona;
        $this->user             = new User;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->has('sort')) {
            list($sortCol, $sortDir) = explode('|', request()->sort);

            $query = $this->sekolah->orderBy($sortCol, $sortDir);
        } else {
            $query = $this->sekolah->orderBy('id', 'asc');
        }

        if ($request->exists('filter')) {
            $query->where(function($q) use($request) {
                $value = "%{$request->filter}%";

                $q->where('nama', 'like', $value)
                    ->orWhere('npsn', 'like', $value)
                    ->orWhere('alamat', 'like', $value)
                    ->orWhere('email', 'like', $value);
            });
        }

        $perPage = request()->has('per_page') ? (int) request()->per_page : null;

        $response = $query->with(['jenis_sekolah', 'province', 'city', 'district', 'village', 'master_zona', 'user'])->paginate($perPage);

        return response()->json($response)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        $sekolahs = $this->sekolah->with(['jenis_sekolah', 'province', 'city', 'district', 'village', 'master_zona', 'user'])->get();

        $response['sekolahs']   = $sekolahs;
        $response['error']      = false;
        $response['message']    = 'Success';
        $response['status']     = true;

        return response()->json($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user_id        = isset(Auth::User()->id) ? Auth::User()->id : null;
        $sekolah        = $this->sekolah->getAttributes();
        $provinces      = $this->province->getAttributes();
        $cities         = $this->city->getAttributes();
        $districts      = $this->district->getAttributes();
        $villages       = $this->village->getAttributes();
        $users          = $this->user->getAttributes();
        $users_special  = $this->user->all();
        $users_standar  = $this->user->findOrFail($user_id);
        $current_user   = Auth::User();

        $role_check = Auth::User()->hasRole(['superadministrator','administrator']);

        if ($role_check) {
            $user_special = true;

            foreach ($users_special as $user) {
                array_set($user, 'label', $user->name);
            }

            $users = $users_special;
        } else {
            $user_special = false;

            array_set($users_standar, 'label', $users_standar->name);

            $users = $users_standar;
        }

        array_set($current_user, 'label', $current_user->name);

        $response['sekolah']        = $sekolah;
        $response['provinces']      = $provinces;
        $response['cities']         = $cities;
        $response['districts']      = $districts;
        $response['villages']       = $villages;
        $response['users']          = $users;
        $response['user_special']   = $user_special;
        $response['current_user']   = $current_user;
        $response['error']          = false;
        $response['message']        = 'Success';
        $response['status']         = true;

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $sekolah = $this->sekolah;

        $validator = Validator::make($request->all(), [
            'nama'              => 'required|max:255',
            'npsn'              => "required|between:4,17|unique:{$this->sekolah->getTable()},npsn,NULL,id,deleted_at,NULL",
            'jenis_sekolah_id'  => "required|exists:{$this->jenis_sekolah->getTable()},id",
            'alamat'            => 'required|max:255',
            'logo'              => 'max:255',
            'foto_gedung'       => 'max:255',
            'province_id'       => "required|exists:{$this->province->getTable()},id",
            'city_id'           => "required|exists:{$this->city->getTable()},id",
            'district_id'       => "required|exists:{$this->district->getTable()},id",
            'village_id'        => "required|exists:{$this->village->getTable()},id",
            'no_telp'           => 'required|digits_between:10,12',
            'email'             => 'required|email|max:255',
            'uuid'              => 'required|max:255',
            'kode_zona'         => "required|exists:{$this->master_zona->getTable()},id",
            'user_id'           => "required|exists:{$this->user->getTable()},id",
        ]);

        if ($validator->fails()) {
            $error      = true;
            $message    = $validator->errors()->first();
        } else {
            $sekolah->id                = $request->input('npsn');
            $sekolah->nama              = $request->input('nama');
            $sekolah->npsn              = $request->input('npsn');
            $sekolah->jenis_sekolah_id  = $request->input('jenis_sekolah_id');
            $sekolah->alamat            = $request->input('alamat');
            $sekolah->logo              = $request->input('logo');
            $sekolah->foto_gedung       = $request->input('foto_gedung');
            $sekolah->province_id       = $request->input('province_id');
            $sekolah->city_id           = $request->input('city_id');
            $sekolah->district_id       = $request->input('district_id');
            $sekolah->village_id        = $request->input('village_id');
            $sekolah->no_telp           = $request->input('no_telp');
            $sekolah->email             = $request->input('email');
            $sekolah->uuid              = $request->input('uuid');
            $sekolah->kode_zona         = $request->input('kode_zona');
            $sekolah->user_id           = $request->input('user_id');
            $sekolah->save();

            $error      = false;
            $message    = 'Success';
        }

        $response['error']      = $error;
        $response['message']    = $message;
        $response['status']     = true;

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Sekolah  $sekolah
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $sekolah = $this->sekolah->with(['jenis_sekolah', 'province', 'city', 'district', 'village', 'master_zona', 'user'])->findOrFail($id);

        $response['sekolah']    = $sekolah;
        $response['error']      = false;
        $response['message']    = 'Success';
        $response['status']     = true;

        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Sekolah  $sekolah
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user_id        = isset(Auth::User()->id) ? Auth::User()->id : null;
        $sekolah        = $this->sekolah->with(['jenis_sekolah', 'province', 'city', 'district', 'village', 'master_zona', 'user'])->findOrFail($id);
        $provinces      = $this->province->getAttributes();
        $cities         = $this->city->getAttributes();
        $districts      = $this->district->getAttributes();
        $villages       = $this->village->getAttributes();
        $users          = $this->user->getAttributes();
        $users_special  = $this->user->all();
        $users_standar  = $this->user->findOrFail($user_id);
        $current_user   = Auth::User();

        if ($sekolah->province !== null) {
            array_set($sekolah->province, 'label', $sekolah->province->name);
        }

        if ($sekolah->city !== null) {
            array_set($sekolah->city, 'label', $sekolah->city->name);
        }

        if ($sekolah->district !== null) {
            array_set($sekolah->district, 'label', $sekolah->district->name);
        }

        if ($sekolah->village !== null) {
            array_set($sekolah->village, 'label', $sekolah->village->name);
        }

        $role_check = Auth::User()->hasRole(['superadministrator','administrator']);

        if ($sekolah->user !== null) {
            array_set($sekolah->user, 'label', $sekolah->user->name);
        }

        if ($role_check) {
            $user_special = true;

            foreach($users_special as $user){
                array_set($user, 'label', $user->name);
            }

            $users = $users_special;
        } else {
            $user_special = false;

            array_set($users_standar, 'label', $users_standar->name);

            $users = $users_standar;
        }

        array_set($current_user, 'label', $current_user->name);

        $response['sekolah']        = $sekolah;
        $response['provinces']      = $provinces;
        $response['cities']         = $cities;
        $response['districts']      = $districts;
        $response['villages']       = $villages;
        $response['users']          = $users;
        $response['user_special']   = $user_special;
        $response['current_user']   = $current_user;
        $response['error']          = false;
        $response['message']        = 'Success';
        $response['status']         = true;

        return response()->json($response);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Sekolah  $sekolah
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $sekolah = $this->sekolah->with(['jenis_sekolah', 'province', 'city', 'district', 'village', 'master_zona', 'user'])->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama'              => 'required|max:255',
            'npsn'              => "required|between:4,17|unique:{$this->sekolah->getTable()},npsn,{$id},id,deleted_at,NULL",
            'jenis_sekolah_id'  => "required|exists:{$this->jenis_sekolah->getTable()},id",
            'alamat'            => 'required|max:255',
            'logo'              => 'max:255',
            'foto_gedung'       => 'max:255',
            'province_id'       => "required|exists:{$this->province->getTable()},id",
            'city_id'           => "required|exists:{$this->city->getTable()},id",
            'district_id'       => "required|exists:{$this->district->getTable()},id",
            'village_id'        => "required|exists:{$this->village->getTable()},id",
            'no_telp'           => 'required|digits_between:10,12',
            'email'             => 'required|email|max:255',
            'uuid'              => "required|unique:{$this->sekolah->getTable()},uuid,{$id},id,deleted_at,NULL",
            'kode_zona'         => "required|exists:{$this->master_zona->getTable()},id",
            'user_id'           => "required|exists:{$this->user->getTable()},id",
        ]);

        if ($validator->fails()) {
            $error      = true;
            $message    = $validator->errors()->first();
        } else {
            $sekolah->id                = $request->input('npsn');
            $sekolah->nama              = $request->input('nama');
            $sekolah->npsn              = $request->input('npsn');
            $sekolah->jenis_sekolah_id  = $request->input('jenis_sekolah_id');
            $sekolah->alamat            = $request->input('alamat');
            $sekolah->logo              = $request->input('logo');
            $sekolah->foto_gedung       = $request->input('foto_gedung');
            $sekolah->province_id       = $request->input('province_id');
            $sekolah->city_id           = $request->input('city_id');
            $sekolah->district_id       = $request->input('district_id');
            $sekolah->village_id        = $request->input('village_id');
            $sekolah->no_telp           = $request->input('no_telp');
            $sekolah->email             = $request->input('email');
            $sekolah->uuid              = $request->input('uuid');
            $sekolah->kode_zona         = $request->input('kode_zona');
            $sekolah->user_id           = $request->input('user_id');
            $sekolah->save();

            $error      = false;
            $message    = 'Success';
        }

        $response['error']      = $error;
        $response['message']    = $message;
        $response['status']     = true;

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Sekolah  $sekolah
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sekolah = $this->sekolah->findOrFail($id);

        if ($sekolah->delete()) {
            $response['message']    = 'Success';
            $response['success']    = true;
            $response['status']     = true;
        } else {
            $response['message']    = 'Failed';
            $response['success']    = false;
            $response['status']     = false;
        }

        return json_encode($response);
    }
}

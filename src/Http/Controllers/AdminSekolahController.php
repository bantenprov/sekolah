<?php

namespace Bantenprov\Sekolah\Http\Controllers;

/* Require */
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Bantenprov\Sekolah\Facades\SekolahFacade;

/* Models */
use Bantenprov\Sekolah\Models\Bantenprov\Sekolah\AdminSekolah;
use Bantenprov\Sekolah\Models\Bantenprov\Sekolah\Sekolah;
use App\User;

/* Etc */
use Validator;
use Auth;

/**
 * The ProdiSekolahController class.
 *
 * @package Bantenprov\Sekolah
 * @author  bantenprov <developer.bantenprov@gmail.com>
 */
class AdminSekolahController extends Controller
{
    protected $admin_sekolah;
    protected $sekolah;
    protected $user;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->admin_sekolah    = new AdminSekolah;
        $this->sekolah          = new Sekolah;
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

            $query = $this->admin_sekolah->orderBy($sortCol, $sortDir);
        } else {
            $query = $this->admin_sekolah->orderBy('id', 'asc');
        }

        if ($request->exists('filter')) {
            $query->where(function($q) use($request) {
                $value = "%{$request->filter}%";

                $q->where('sekolah_id', 'like', $value)
                    ->orWhere('admin_sekolah_id', 'like', $value);
            });
        }

        $perPage = request()->has('per_page') ? (int) request()->per_page : null;

        $response = $query->with(['admin_sekolah', 'sekolah', 'user'])->paginate($perPage);

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
        $admin_sekolahs = $this->admin_sekolah->with(['sekolah', 'user'])->get();

        $response['admin_sekolahs'] = $admin_sekolahs;
        $response['error']          = false;
        $response['message']        = 'Success';
        $response['status']         = true;

        return response()->json($response);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getBySekolah($id)
    {
        $admin_sekolahs = $this->admin_sekolah->where('sekolah_id', '=', $id)->with(['sekolah', 'user'])->get();

        $response['admin_sekolahs'] = $admin_sekolahs;
        $response['message']        = 'Success';
        $response['error']          = false;
        $response['status']         = true;

        return response()->json($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user_id            = isset(Auth::User()->id) ? Auth::User()->id : null;
        $admin_sekolah      = $this->admin_sekolah->getAttributes();
        //$program_keahlians  = $this->program_keahlian->all();
        $users              = $this->user->getAttributes();
        $users_special      = $this->user->all();
        $users_standar      = $this->user->findOrFail($user_id);
        $current_user       = Auth::User();

        /*foreach ($program_keahlians as $program_keahlian) {
            array_set($program_keahlian, 'label', $program_keahlian->label);
        }*/

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

        $response['admin_sekolah']      = $admin_sekolah;
        //$response['program_keahlians']  = $program_keahlians;
        $response['users']              = $users;
        $response['user_special']       = $user_special;
        $response['current_user']       = $current_user;
        $response['error']              = false;
        $response['message']            = 'Success';
        $response['status']             = true;

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
        $admin_sekolah = $this->admin_sekolah;

        $validator = Validator::make($request->all(), [
            'sekolah_id'            => "required|exists:{$this->sekolah->getTable()},id",
            'admin_sekolah_id'      => "required|unique:{$this->admin_sekolah->getTable()},admin_sekolah_id,NULL,id,deleted_at,NULL",
            'user_id'               => "required|exists:{$this->user->getTable()},id",
        ]);

        if ($validator->fails()) {
            $error      = true;
            $message    = $validator->errors()->first();
        } else {
            $admin_sekolah->sekolah_id          = $request->input('sekolah_id');
            $admin_sekolah->admin_sekolah_id    = $request->input('admin_sekolah_id');
            $admin_sekolah->user_id             = $request->input('user_id');
            $admin_sekolah->save();

            $error      = false;
            $message    = 'Success';
        }

        $response['admin_sekolah']  = $admin_sekolah;
        $response['error']          = $error;
        $response['message']        = $message;
        $response['status']         = true;

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ProdiSekolah  $prodi_sekolah
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $admin_sekolah = $this->admin_sekolah->with(['sekolah', 'admin_sekolah', 'user'])->findOrFail($id);

        $response['admin_sekolah']  = $admin_sekolah;
        $response['error']          = false;
        $response['message']        = 'Success';
        $response['status']         = true;

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
        $user_id            = isset(Auth::User()->id) ? Auth::User()->id : null;
        $admin_sekolah      = $this->admin_sekolah->with(['sekolah', 'admin_sekolah', 'user'])->findOrFail($id);
        $users              = $this->user->getAttributes();
        $users_special      = $this->user->all();
        $users_standar      = $this->user->findOrFail($user_id);
        $current_user       = Auth::User();


        $role_check = Auth::User()->hasRole(['superadministrator','administrator']);

        if ($admin_sekolah->user !== null) {
            array_set($admin_sekolah->user, 'label', $admin_sekolah->user->name);
        }

        if ($admin_sekolah->admin_sekolah !== null) {
            array_set($admin_sekolah->admin_sekolah, 'label', $admin_sekolah->admin_sekolah->name);
        }

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

        $response['admin_sekolah']      = $admin_sekolah;
        $response['users']              = $users;
        $response['user_special']       = $user_special;
        $response['current_user']       = $current_user;
        $response['error']              = false;
        $response['message']            = 'Success';
        $response['status']             = true;

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
        $admin_sekolah = $this->admin_sekolah->with(['sekolah', 'admin_sekolah', 'user'])->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sekolah_id'            => "required|exists:{$this->sekolah->getTable()},id",
            'admin_sekolah_id'      => "required|unique:{$this->admin_sekolah->getTable()},admin_sekolah_id,{$id},id,deleted_at,NULL",
            'user_id'               => "required|exists:{$this->user->getTable()},id",
        ]);

        if ($validator->fails()) {
            $error      = true;
            $message    = $validator->errors()->first();
        } else {
            $admin_sekolah->sekolah_id          = $request->input('sekolah_id');
            $admin_sekolah->admin_sekolah_id    = $request->input('admin_sekolah_id');
            $admin_sekolah->user_id             = $request->input('user_id');
            $admin_sekolah->save();

            $error      = false;
            $message    = 'Success';
        }

        $response['admin_sekolah']  = $admin_sekolah;
        $response['error']          = $error;
        $response['message']        = $message;
        $response['status']         = true;

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
        $admin_sekolah = $this->admin_sekolah->findOrFail($id);

        if ($admin_sekolah->delete()) {
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
<?php
    
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;

use App\Services\UserProfileService;
use App\Traits\RoleTrait;
    
class RoleController extends Controller
{
    use RoleTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Roles', ['only' => ['index','store', 'show', 'edit', 'update','destroy', 'activateRole']]);
        $this->userProfileObj = new UserProfileService(); // user profile Service object
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
    	$user_id = auth()->user()->id;
        $roles = Role::orderBy('id','DESC')->get();
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $permission = Permission::get();
        return view('roles.index',compact('roles','user_profile_data','permission'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }
    
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);
    
        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));
    
        return redirect()->route('roles.index')
                        ->with('success','Role created successfully');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    	$user_id = auth()->user()->id;
    	$user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id",$id)
            ->get();
    
        return view('roles.show',compact('role','rolePermissions','user_profile_data'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // dd($id);
    	$user_id = auth()->user()->id;
    	$user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();

        return response()->json(['status' => 201, 'response' => 'success','rolePermissions' => $rolePermissions,'role'=> $role->name,'label'=> $role->label]);
        //return view('roles.edit',compact('role','permission','rolePermissions','user_profile_data'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // $this->validate($request, [
        //     'name' => 'required',
        //     'permission' => 'required',
        // ]);
    
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->label = $request->input('label');
        $role->save();
    
        $role->syncPermissions($request->input('permission'));
    
        return redirect()->route('roles.index')
                        ->with('success','Role updated successfully');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = $this->deleteRole($id);
        return redirect()->route('roles.index')
                        ->with('success','Role deactivated successfully');
    }

    /**
     * If specific role exist in users storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return boolean
     */
    public function isExist(Request $request)
    {
        $result = $this->isRoleExist($request->id);
        if($result > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * activate role from storage.
     *
     * @param  int $id
     * @return boolean
     */
    public function activateRole($id)
    {
        $result = $this->activateRoles($id);
        return redirect()->route('roles.index')
                        ->with('success','Role activated successfully');
    }
}
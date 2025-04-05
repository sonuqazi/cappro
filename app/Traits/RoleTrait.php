<?php

namespace App\Traits;

use App\Models\Roles;
use App\Models\User;


Trait RoleTrait
{


    /** 
     * Get all roles
     *
     * @return $roles
     */
    public function getRoles()
    {

        $roles = Roles::where('status', 1)->get();
        return $roles;
    }

    /** 
     * Inactivate Role
     * @param int $id
     * @return boolean
     */
    public  function deleteRole($id)
    {
        Roles::where('id', $id)->update(['status' => 0]);
        return true;
    }

    /** 
     * Check Role
     * @param int $id
     * @return $count
     */
    public  function isRoleExist($id=null)
    {
        $count = User::where('role_id', $id)->where('is_deleted', 0)->count();
        return $count;
    }

    /** 
     * Activate Role
     * @param int $id
     * @return boolean
     */
    public  function activateRoles($id)
    {
        Roles::where('id', $id)->update(['status' => 1]);
        return true;
    }
}

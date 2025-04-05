<?php
namespace App\Services;

use App\Models\Categories;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UniversityUser;
class CategoryService
{
    /**
     * function for get all team assign to user by id  
     * @retrun object
     */
    public function getCategory(){
		//  DB::enableQueryLog();
        $loginUserId=Auth::user()->id;
		$categories =  Categories::select('categories.id', 'categories.title', 'categories.description', 'categories.is_active')->where('is_active', 1)->where('is_deleted', 0)->orderBy('categories.id', 'ASC')->get();
		// dd(DB::getQueryLog());
		return $categories;
	}


    /**
     * @description insert the money spend 
     * @param object $request
     * @param int $userId
     */
    public function insertCategory($request)
    {
        $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
        // $data = array(
        //     'title' => $request->input('title'),
        //     'description' => $request->input('description'),
        //     'created_by' => $userId->id
        // );
        $data['title'] = $request->input('title');
        $data['description'] = $request->input('description');
        $data['created_by'] = $userId->id;
        //dd($data);
        $return = Categories::insert($data);
        return $return;
        //return Categories::create($data);
    }

    /**
     * @description update the category
     * @param $request(object)
     * @return object
     */
    public function updateCategory($request)
    {
        $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
        $id = $request->input('editCategoryId');
        $data = array(
            'title' => $request->input('editTitle'),
            'description' => $request->input('editDescription'),
            'updated_by' => $userId->id,
            'updated_at' => $request->input('updated_at'),
        );
        return Categories::whereId($id)->update($data);
    }
    /**
     * @description update the category
     * @param int $id, string $status
     * @return boolean
     */

     public function deleteCategory(int $id = null, $status = null)
    {
       Categories::where('id', $id)->update(['is_active' => $status]);
       return true;
    }

    /**
     * function for get all deactivated categories
     * @retrun object
     */
    public function deactivatedCategories(){
        $loginUserId=Auth::user()->id;
		$categories =  Categories::select('categories.id', 'categories.title', 'categories.description', 'categories.is_active')->where('is_active', 0)->orderBy('categories.id', 'ASC')->get();
		return $categories;
	}
}

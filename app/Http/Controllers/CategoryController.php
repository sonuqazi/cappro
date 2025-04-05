<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\CategoryService;
use Throwable;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Project Categories', ['only' => ['index','store','update','destroy','active','deactivatedCategories']]);
        $this->categoryObj = new CategoryService();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categoryList = $this->categoryObj->getCategory();
        //dd($categoryList);
        return view('category.index', ['categoryList' => $categoryList]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                'title' => ['required'],
            ]);
            if ($validator->fails())
                return redirect()->route('getCategories')->withErrors($validator);
            $result = $this->categoryObj->insertCategory($request);
            if ($result)
                return redirect()->route('getCategories')->with('success', 'Category added successfully.');
            return redirect()->route('getCategories')->with('error', 'Category added failed.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
                'editTitle' => ['required'],
            ]);
            if ($validator->fails())
                return redirect()->route('getCategories')->withErrors($validator);

            $result = $this->categoryObj->updateCategory($request);
            if ($result)
                return redirect()->back()->with('success', 'Category updated successfully.');
            return redirect()->back()->with('error', 'Category updated failed.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = $this->categoryObj->deleteCategory($id, 0);
        if ($result)
            return redirect()->back()->with('success', 'Category deactivated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active($id)
    {
        $result = $this->categoryObj->deleteCategory($id, 1);
        if ($result)
            return redirect()->back()->with('success', 'Category activated successfully.');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivatedCategories()
    {
        $categoryList = $this->categoryObj->deactivatedCategories();
        //dd($categoryList);
        return view('category.deactivatedCategories', ['categoryList' => $categoryList]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProjectService;
use App\Services\UserProfileService;
use App\Models\Semester;

class SemesterController extends Controller
{
     /**
     * Create a new controller instance.
     *
     * @return void
     */
     public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:Manage Semesters', ['only' => ['index','store','edit']]);
        $this->projectObj = new ProjectService();
        $this->userProfileObj = new UserProfileService(); // user profile Service object
    }
    
    /**
     * List of all semester
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
     public function index() {
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $semesters = Semester::with('courses')->orderBy('sort_code','desc')->get();
            // dd($semesters);
            return view('semesters.index', ['user_profile_data' => $user_profile_data,'semesters' => $semesters]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('semesters')->with('error', $e);
        }
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
     public function store(Request $request)
     {
        if (Semester::where('semester', '=', $request->input('semester'))
                    ->where('year', '=', $request->input('year'))->count() > 0) 
            {
                        return redirect()->route('semesters')
                        ->with('error','Semester Already Exits!');
            }else{
                if($request->input('semester') == 'Spring'){
                    $sem = 2;
                }elseif($request->input('semester') == 'Summer'){
                    $sem = 5;
                }elseif($request->input('semester') == 'Fall'){
                    $sem = 8;
                }        
                $year = substr_replace($request->input('year'), '', 1, 1);
                $sortCode = $year.$sem;
                
                $sem = Semester::create(['semester' => $request->input('semester'),'year' => $request->input('year'),'sort_code' => $sortCode,'description'=> $request->input('description')]);
                return redirect()->route('semesters')
                                ->with('success','Semester added successfully');
            }
        
     }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request){
        $data = array(
            'semester' => $request['semester'],
            'year' => $request['year'],
            'description' => $request['description']
        );
        Semester::where('id', $request['id'])->update($data);
        return redirect()->route('semesters')->with('success', 'Semester updated successfully.');
    }
}

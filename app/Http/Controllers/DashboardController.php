<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\addProject;
use Illuminate\Support\Facades\Mail;

use App\Models\Project;
use App\Models\ProjectCourse;
use App\Models\Team;
use App\Models\TeamStudent;
use App\Models\Categories;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;
use App\Models\ChangeRequest;

use App\Traits\ProjectTrait;
use App\Traits\RoleTrait;
use App\Traits\DiscussionTrait;

use App\Services\ProjectService;
use App\Services\UserProfileService;
use App\Services\UploadFilesService;
use App\Services\MessageService;
use App\Services\MoneySpendService;
use App\Services\TimeSpendService;
use App\Services\PeerEvaluationService;

class DashboardController extends Controller
{
    use ProjectTrait;
    use RoleTrait;
    use DiscussionTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Admin|Faculty', ['only' => ['index']]);
        $this->projectObj = new ProjectService();
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->messageObj = new MessageService(); // user message Service object
        $this->uploadFileObj = new UploadFilesService(); // upload file Service object
        $this->moneySpentObj = new MoneySpendService();
        $this->timeSpentObj = new TimeSpendService();
        $this->peerEvalObj = new PeerEvaluationService();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projects = $sum = [];
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            
            //dd(array_sum($sum));
            $all_semesters = $this->projectObj->getAllSemesters();
            $semester = $this->projectObj->getCurrentSemester();
            $semester_id = $semester->id;
            $allActiveProjects = $this->projectObj->getAllActiveProjects($semester_id);
            // foreach($allActiveProjects as $key => $project){
            //     $sum[]= count($project->project_course);
            // }
            $allTeams = $this->projectObj->getAllTeams($semester_id);
            //dd($semester_id);
            $allCourses = $this->projectObj->getAllCourses($semester_id);
            $allClients = $this->projectObj->getAllClients($semester_id);
            $allCompletedProjects = $this->projectObj->getAllCompletedProjects();
            $allFaculties = $this->projectObj->getAllFaculties($semester_id);
            $allStudents = $this->projectObj->getAllStudents($semester_id);
            $allFiles = $this->projectObj->getAllFiles($semester_id);
            $allDiscussions = $this->getAllDiscussions($semester_id);
            $allMilestones['milestoneCount'] = $this->projectObj->getAllMilestoneCount($semester_id);
            $allMilestones['completedMilestones'] = $this->projectObj->getAllCompletedMilestone($semester_id);
            $allMoneySpent = $this->moneySpentObj->getAllMoneySpent($semester_id);
            $allHoursSpent = $this->timeSpentObj->getAllHoursSpent($semester_id);
            $allTasks['taskCount'] = $this->projectObj->getAllTasks($semester_id);
            $allTasks['completedTasks'] = $this->projectObj->getAllCompletedTasks($semester_id);
            $allPeerEvaluations['peerEvaluationCount'] = $this->peerEvalObj->getAllPeerEvaluation($semester_id);
            $allPeerEvaluations['completedPeerEvaluation'] = $this->peerEvalObj->getAllCompletedPeerEvaluation($semester_id);
            $projects['allActiveProjects'] = $allActiveProjects;
            //$projects['activeProjectsCount'] = array_sum($sum);
            $projects['allTeams'] = $allTeams;
            $projects['teamsCount'] = count($allTeams);
            $projects['allCompletedProjects'] = $allCompletedProjects;
            $projects['completedProjectsCount'] = count($allCompletedProjects);
            $projects['allClients'] = $allClients;
            $projects['clientsCount'] = count($allClients);
            $projects['facultiesCount'] = $allFaculties;
            $projects['studentsCount'] = count($allStudents);
            $projects['discussionsCount'] = $allDiscussions;
            $projects['filesCount'] = $allFiles;
            $projects['milestoneCount'] = $allMilestones;
            $projects['moneySpentCount'] = number_format($allMoneySpent,2);
            $projects['hoursSpentCount'] = number_format($allHoursSpent,2);
            $projects['tasksCount'] = $allTasks;
            $projects['peerEvaluationCount'] = $allPeerEvaluations;
            $projects['allCourses'] = $allCourses;
            //dd($projects['allActiveProjects'][0]);
            return view('dashboard.index',compact('user_profile_data', 'projects', 'all_semesters', 'semester_id'));
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }

    /**
     * get stats semester wise
     * Uses ajax request
     *
     * @param \Illuminate\Http\Request  $request
     * @return JSON
    */

    public function statsSemesterWise(Request $request)
    {
        //dd($request->all());
        $projects = $sum = [];
        $allActiveProjects = $this->projectObj->getAllActiveProjects($request->semester_id);
        // foreach($allActiveProjects as $key => $project){
        //     $sum[]= count($project->project_course);
        // }
        $allTeams = $this->projectObj->getAllTeams($request->semester_id);
        //dd($semester_id);
        $allCourses = $this->projectObj->getAllCourses($request->semester_id);
        $allClients = $this->projectObj->getAllClients($request->semester_id);
        $allCompletedProjects = $this->projectObj->getAllCompletedProjects();
        $allFaculties = $this->projectObj->getAllFaculties($request->semester_id);
        $allStudents = $this->projectObj->getAllStudents($request->semester_id);
        $allFiles = $this->projectObj->getAllFiles($request->semester_id);
        $allDiscussions = $this->getAllDiscussions($request->semester_id);
        $allMilestones['milestoneCount'] = $this->projectObj->getAllMilestoneCount($request->semester_id);
        $allMilestones['completedMilestones'] = $this->projectObj->getAllCompletedMilestone($request->semester_id);
        $allMoneySpent = $this->moneySpentObj->getAllMoneySpent($request->semester_id);
        $allHoursSpent = $this->timeSpentObj->getAllHoursSpent($request->semester_id);
        $allTasks['taskCount'] = $this->projectObj->getAllTasks($request->semester_id);
        $allTasks['completedTasks'] = $this->projectObj->getAllCompletedTasks($request->semester_id);
        $allPeerEvaluations['peerEvaluationCount'] = $this->peerEvalObj->getAllPeerEvaluation($request->semester_id);
        $allPeerEvaluations['completedPeerEvaluation'] = $this->peerEvalObj->getAllCompletedPeerEvaluation($request->semester_id);
        $projects['allActiveProjects'] = $allActiveProjects;
        //$projects['activeProjectsCount'] = array_sum($sum);
        $projects['teamsCount'] = count($allTeams);
        $projects['clientsCount'] = count($allClients);
        $projects['facultiesCount'] = $allFaculties;
        $projects['studentsCount'] = count($allStudents);
        $projects['discussionsCount'] = $allDiscussions;
        $projects['filesCount'] = $allFiles;
        $projects['milestoneCount'] = $allMilestones['completedMilestones'].'/'.$allMilestones['milestoneCount'];
        $projects['moneySpentCount'] = number_format($allMoneySpent,2);
        $projects['hoursSpentCount'] = number_format($allHoursSpent,2);
        $projects['tasksCount'] = $allTasks['completedTasks'].'/'.$allTasks['taskCount'];
        $projects['peerEvaluationCount'] = $allPeerEvaluations['completedPeerEvaluation'].'/'.$allPeerEvaluations['peerEvaluationCount'];
        $projects['allCourses'] = $allCourses;
        return response()->json($projects);
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
        //
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

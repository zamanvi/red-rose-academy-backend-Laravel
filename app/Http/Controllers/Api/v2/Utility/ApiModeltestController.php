<?php

namespace App\Http\Controllers\Api\v2\Utility;

use App\Http\Controllers\Controller;
use App\Models\ModelQuestion;
use App\Models\ModelSyllabus;
use App\Models\ModelTestAll;
use App\Models\ModelTestResult;
use App\Models\User;
use App\Traits\AppResponse;
use App\Traits\HttpAppResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ApiModeltestController extends Controller
{
    use HttpAppResponse;
    public function all_modeltest()
    {
        $modeltests = ModelTestAll::where('type', '=', 'on')->get(['id', 'class_id', 'name', 'subject', 'type', 'duration']);
        return $this->apiResponse(
            [
                'modeltests' => $modeltests,
            ],
            true,
            'All Modeltests accroding to selected clsass read successful.!',
            AppResponse::HTTP_OK,
        );
    }
    public function my_modeltest(Request $request)
    {
        $user = Auth::user();
        if ($user->as_user == 'student') {
            $modeltests = ModelTestAll::where('class_id', $user->class_department)
                ->where('type', '=', 'on')
                ->get(['id', 'class_id', 'name', 'subject', 'type', 'duration']);
            return $this->apiResponse(
                [
                    'modeltests' => $modeltests,
                ],
                true,
                'All Modeltests accroding to your clsass read successful.!',
                AppResponse::HTTP_OK,
            );
        } else {
            return $this->apiResponse('', false, 'You are not student, please make your profile as student and try again latter.!', AppResponse::HTTP_OK);
        }
    }
    public function modeltest_syllabus(Request $request, $id)
    {
        $syllabs = ModelSyllabus::where('modeltest_id', $id)->first();
        if ($syllabs) {
            return $this->apiResponse(
                [
                    'syllabs' => $syllabs,
                ],
                true,
                'Read Model test syllabus',
                AppResponse::HTTP_OK,
            );
        } else {
            return $this->apiResponse('', false, 'Dear user, Please wait for upload ' . 'Modeltest\'s syllabus. Thank you.!', AppResponse::HTTP_NOT_FOUND);
        }
    }
    public function modeltest_take_exam(Request $request, $id)
    {
        $modeltest = ModelTestAll::find($id);
        if (ModelQuestion::where('modeltest_id', $id)->first()) {
            $question = ModelQuestion::where('modeltest_id', $id)
                ->inRandomOrder()
                ->get(['id', 'modeltest_id', 'name', 'option1', 'option2', 'option3', 'option4', 'option5']);
            return $this->apiResponse(
                [
                    'modeltest' => $modeltest,
                    'totalquestion' => $question->count(),
                    'question' => $question,
                ],
                true,
                'Modeltest Exam with total question.!',
                AppResponse::HTTP_OK,
            );
        } else {
            return $this->apiResponse('', false, 'Dear user, Please wait for upload ' . 'Modeltest\'s question. Thank you.!', AppResponse::HTTP_NOT_FOUND);
        }
    }

    public function modeltest_submit_exam_for_result(Request $request)
    {
        $data = [
            'modeltest_id' => $request->input('modeltest_id'),
            'totalquestion' => $request->input('totalquestion'),
            'ans' => $request->input('ans'),
        ];
        $roules = [
            'modeltest_id' => 'required',
            'totalquestion' => 'required',
            'ans' => 'required',
        ];

        if ($this->request_validator($data, $roules)) {
            return $this->apiResponse('', false, 'Make sure you need to fill all the required parametter.', 200);
        } else {
            $id = $request['modeltest_id'];
            $user = User::find(Auth::user()->id);
            $modeltest = ModelTestAll::find($id);
            $right = 0;
            $wrong = 0;
            foreach ($request->input('ans') as $k => $ans) {
                $question_id = Str::substr($ans, 0, 1);
                $optopn = Str::substr($ans, 2, 1);
                if ($this->getCurrectAnsResult($question_id, $optopn)) {
                    $right++;
                } else {
                    $wrong++;
                }
                $give_ans = $k + 1;
            }
            $negative = $wrong / 4;
            $mark = $right - $negative;
            $totalquestion = $request->input('totalquestion');
            $totalpoint = $user->profile->points + $mark;
            $not_give_ans = $totalquestion - $give_ans;

            $modeltestresult = ModelTestResult::where('modeltest_id', $id)
                ->where('user_id', $user->id)
                ->first();
            if ($modeltestresult) {
                $this->modeltestresult_make($id, $user->id, 'Repeat', $totalquestion, $right, $wrong, $mark, $negative, $give_ans, $not_give_ans);
                $this->notification($user->id, '"' . $user->name . '" get ' . $mark . ' from repeat Modeltest "' . $modeltest->name . '".!',  'modeltest', '1');
            } else {
                $this->modeltestresult_make($id, $user->id, '1st Time', $totalquestion, $right, $wrong, $mark, $negative, $give_ans, $not_give_ans);
                $this->pointupdate($user->id, $totalpoint);
                $this->history($user->id, 'You get "' . $mark . '" points from "' . $modeltest->name . '" Modeltest Exam.! Where total question "' . $totalquestion . '", right answered "' . $right . '", wrong answered "' . $wrong . '", nagetive mark "' . $negative . '", total mark "' . $mark . '"', 'modeltest');
                $this->notification($user->id, '"' . $user->name . '" get ' . $mark . ' from Modeltest "' . $modeltest->name . '".!',  'modeltest', '1');
            }
            $mtresults = ModelTestResult::where('modeltest_id', $id)
                ->where('user_id', Auth::user()->id)
                ->get(['id', 'modeltest_id', 'user_id', 'total_q', 'r_ans', 'w_ans', 'total_mark', 'neg_mark', 'give_ans', 'not_give_ans']);
            return $this->apiResponse(
                [
                    'mtresults' => $mtresults,
                ],
                true,
                'Modeltest result.!',
                AppResponse::HTTP_OK,
            );
        }
    }
    public function modeltest_get_result($id)
    {
        $modelTest = ModelTestAll::find($id);
        if ($modelTest) {
            if (
                ModelTestResult::where('modeltest_id', $id)
                    ->where('user_id', Auth::user()->id)
                    ->exists()
            ) {
                $results = ModelTestResult::where('modeltest_id', $id)
                    ->where('user_id', Auth::user()->id)
                    ->get(['id', 'modeltest_id', 'user_id', 'type', 'total_q', 'r_ans', 'w_ans', 'total_mark', 'neg_mark', 'give_ans', 'not_give_ans']);
                return $this->apiResponse(
                    [
                        'results' => $results,
                    ],
                    true,
                    'The result of modeltest exam is ready to display.!',
                    AppResponse::HTTP_OK,
                );
            } else {
                return $this->apiResponse('', false, 'You not take any modeltest exam, Please take one modeltet first.!', AppResponse::HTTP_NOT_FOUND);
            }
        } else {
            return $this->apiResponse('', false, 'Can\'t find any Modeltest, Please try again.!', AppResponse::HTTP_NOT_FOUND);
        }
    }
}

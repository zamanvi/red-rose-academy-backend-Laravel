<?php

namespace App\Http\Controllers\Api\v2\Utility;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestEnroll;
use App\Models\ContestQuestion;
use App\Models\Result;
use App\Models\User;
use App\Traits\AppResponse;
use App\Traits\HttpAppResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiContestController extends Controller
{
    use HttpAppResponse;

    public function contest(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $status = $request->input('status', true); // Default to true for active contests
        $message = $status ? 'All active contests read successfully!' : 'All inactive contests read successfully!';
        $contests = Contest::active($status)
            ->paginate($perPage, [
                'id', 'slug', 'name', 'date', 'time', 'price', 'status', 'image_path', 'duration'
            ]);
        return $this->apiResponse(['contests' => $contests], true, $message, AppResponse::HTTP_OK);
    }
    public function contest_show($slug)
    {
        $contest = Contest::where('slug', $slug)->firstOrFail();
        $contestEnrolls = ContestEnroll::where('contest_id', $contest->id)->pluck('user_id');
        $users = User::whereIn('id', $contestEnrolls)->get(['id', 'name', 'email', 'redrose_id', 'profile_photo_path']);
        $results = [];
        if (!$contest->status) {
            $results = Result::with([
                    'user' => function ($query) {
                        $query->select('id', 'redrose_id', 'name', 'email', 'as_user', 'profile_photo_path');
                    }
                ])
                ->where('contest_id', $contest->id)
                ->orderBy('total_mark', 'desc')
                ->get(['id', 'contest_id', 'user_id', 'total_q', 'r_ans', 'w_ans', 'total_mark', 'neg_mark', 'give_ans', 'not_give_ans', 'is_in_com']);
            get_features($results);
        }
        return $this->apiResponse([
            'contest' => $contest,
            'enrolls' => ['users' => $users],
            'results' => $results
        ], true, 'Contest details with syllabus & Participant list.!', AppResponse::HTTP_OK);
    }
    public function contest_my()
    {
        $contestEnrolls = ContestEnroll::where('user_id', Auth::user()->id)->pluck('contest_id');
        $contests = Contest::whereIn('id', $contestEnrolls)->get();
        return $this->apiResponse(['contests' => $contests], true, 'My contests read successful.!', AppResponse::HTTP_OK);
    }
    public function contest_enroll($slug)
    {
        $contest = Contest::where('slug', $slug)->first();
        $user = Auth::user();
        if ($contest->status) {
            $existEnroll = ContestEnroll::where('contest_id', $contest->id)
                ->where('user_id', $user->id)
                ->first();
            if ($existEnroll) {
                return $this->apiResponse(['contest' => $contest], false, 'You already enroll this contest.!', AppResponse::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                if ($contest->price == 0) {
                    ContestEnroll::create(['contest_id' => $contest->id, 'user_id' => $user->id]);
                    $this->notification($user->id, '"' . Auth::user()->name . '" successfully enroll in contest "' . $contest->name . '"', 'enroll', '1');
                    return $this->apiResponse(['contest' => $contest], true, 'You have successfully enroll this free contest "' . $contest->name . '"', AppResponse::HTTP_OK);
                } else {
                    if ($user->points >= $contest->price) {
                        $update_points = $user->points - $contest->price;
                        ContestEnroll::create(['contest_id' => $contest->id, 'user_id' => $user->id]);
                        $this->notification($user->id, '"' . Auth::user()->name . '" successfully enroll in contest "' . $contest->name . '"', 'enroll', '1');
                        $this->pointupdate($user->id, $update_points);
                        $this->history($user->id, 'You enroll an contest "' . $contest->name . '", and reduce point "' . $update_points . '"', 'contestenroll');
                        return $this->apiResponse(['contest' => $contest], true, 'You have successfully enroll this paid contest "' . $contest->name . '"', AppResponse::HTTP_OK);
                    } else {
                        return $this->apiResponse('', false, 'You do not have enough points to enroll this contest, please increase your point and try again, Thank you for stay with us.!', AppResponse::HTTP_INSUFFICIENT_STORAGE);
                    }
                }
            }
        } else {
            return $this->apiResponse('', false, 'This contest is inactive now, please try to enroll an active contest, Thank you for stay with us.!', AppResponse::HTTP_LOCKED);
        }
    }
    public function contest_take_exam($slug)
    {
        $contest = Contest::where('slug', $slug)->first();
        if (
            Result::where('contest_id', $contest->id)
                ->where('user_id', Auth::user()->id)
                ->exists()
        ) {
            return $this->apiResponse('', false, 'You already take this exam....!', AppResponse::HTTP_UNPROCESSABLE_ENTITY);
        } else {
            if ($contest->status) {
                $question = ContestQuestion::where('contest_id', $contest->id)
                    ->inRandomOrder()
                    ->get(['id', 'name', 'option1', 'option2', 'option3', 'option4']);
                return $this->apiResponse(
                    [
                        'contest' => $contest,
                        'totalquestion' => $question->count(),
                        'question' => $question,
                    ],
                    true,
                    'Exam Question',
                    AppResponse::HTTP_OK,
                );
            } else {
                return $this->apiResponse('', false, 'This exam not start right now...!', AppResponse::HTTP_NOT_FOUND);
            }
        }
    }
    public function contest_submit_exam_for_result(Request $request)
    {
        $data = [
            'contest_id' => $request->input('contest_id'),
            'totalquestion' => $request->input('totalquestion'),
            'ans' => $request->input('ans'),
        ];
        $roules = [
            'contest_id' => 'required',
            'totalquestion' => 'required',
            'ans' => 'required',
        ];
        if ($this->request_validator($data, $roules)) {
            return $this->apiResponse('', false, 'Make sure you need to fill all the required parametter.', 422);
        } else {
            $id = $request['contest_id'];
            $contest = Contest::find($id);
            if ($contest->status) {
                $user = Auth::user();
                $inputString = $request->input('ans');
                $answers = json_decode($inputString, true);
                $right = 0;
                $wrong = 0;
                foreach ($answers as $k => $ans) {
                    $question_id = $ans['q'];
                    $optopn = $ans['o'];
                    if ($this->getCurrectAns($question_id, $optopn)) {
                        $right++;
                    } else {
                        $wrong++;
                    }
                    $give_ans = $k + 1;
                }
                $negative = $wrong / 4;
                $mark = $right - $negative;
                $totalquestion = $request->input('totalquestion');
                $not_give_ans = $totalquestion - $give_ans;
                $totalpoint = $user->points + $mark;
                $this->pointupdate($user->id, $totalpoint);
                $this->history($user->id, 'You get "' . $mark . '" points from "' . $contest->name . '" Contest Exam.! Where total question "' . $totalquestion . '", right answered "' . $right . '", wrong answered "' . $wrong . '", nagetive mark "' . $negative . '", total mark "' . $mark . '"', 'exam');
                $this->notification($user->id, '"' . $user->name . '" get ' . $mark . ' from Contest "' . $contest->name . '".!', 'exam', '1');
                $this->result_make($id, $user->id, $totalquestion, $right, $wrong, $mark, $negative, $give_ans, $not_give_ans);
                $results = Result::where('contest_id', $id)
                    ->where('user_id', $user->id)
                    ->get(['id', 'contest_id', 'user_id', 'total_q', 'r_ans', 'w_ans', 'total_mark', 'neg_mark', 'give_ans', 'not_give_ans']);
                return $this->apiResponse(['results' => $results], true, 'Result of this contest is.', AppResponse::HTTP_OK);
            } else {
                return $this->apiResponse('', false, 'You try to submit an exam which is inactive, Try to take exam which is active.', AppResponse::HTTP_OK);
            }

        }
    }
    public function contest_get_result($slug)
    {
        $contest = Contest::where('slug', $slug)->first();
        $results = Result::with(['user:id,redrose_id,name,email,as_user'])->where('contest_id', $contest->id)->orderby('total_mark', 'desc')
            ->get(['id', 'contest_id', 'user_id', 'total_q', 'r_ans', 'w_ans', 'total_mark', 'neg_mark', 'give_ans', 'not_give_ans', 'is_in_com']);
        return $this->apiResponse(['results' => $results], true, 'The result of contest exam is ready to display.!', AppResponse::HTTP_OK);
    }
    public function contest_get_single_result($slug)
    {
        $contest = Contest::where('slug', $slug)->first();
        $result = Result::where('contest_id', $contest->id)->where('user_id', auth()->user()->id)->first();
        return $this->apiResponse(['result' => $result], true, 'Individual result.!', AppResponse::HTTP_OK);
    }
}

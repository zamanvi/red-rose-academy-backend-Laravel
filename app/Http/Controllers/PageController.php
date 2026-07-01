<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AllClass;
use App\Models\City;
use App\Models\Country;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Http\Request;
use App\Traits\HttpWebResponse;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    use HttpWebResponse;
    public function country_index()
    {
        $countrylist = Country::paginate(25);
        return view('admin.address.country.index', [
            'countrylist' => $countrylist,
        ]);
    }
    public function country_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        Country::create([
            'name' => $request['name'],
            'is_active' => 'off',
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" add Country "' . $request->input('name') . '" successfully.!',  'country', '1');
        return back()->with('success', '"' . $request['name'] . '" Country added successful.!');
    }
    public function country_active(Request $request, $id)
    {
        Country::where('id', $id)->update([
            'is_active' => $request['is_active'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" active Country "' . $request->input('name') . '" successfully.!',  'country', '1');
        return back()->with('success', '"' . $request['name'] . ' Country Active.!');
    }
    public function country_inactive(Request $request, $id)
    {
        Country::where('id', $id)->update([
            'is_active' => $request['is_active'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" inactive Country "' . $request->input('name') . '" successfully.!',  'country', '1');
        return back()->with('success', '"' . $request['name'] . ' Country Inactive.!');
    }
    public function country_edit($id)
    {
        $country = Country::find($id);
        $countrylist = Country::paginate(25);
        return view('admin.address.country.edit', [
            'country' => $country,
            'countrylist' => $countrylist,
        ]);
    }
    public function country_update(Request $request, $id)
    {
        Country::where('id', $id)->update([
            'name' => $request['name'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" update Country "' . $request->input('name') . '" successfully.!',  'country', '1');
        return back()->with('success', '"' . $request['name'] . ' Country name update.!');
    }
    public function country_delete($id)
    {

        $country = Country::find($id);
        $country->delete();
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" delete Country "' . $country->name . '" successfully.!',  'country', '1');
        return redirect(route('country.index'))->with('success', '"' . $country->name . ' Country delete Successful.!');
    }
    public function division_index($id)
    {
        $country = Country::find($id);
        $divisionlist = Division::where('country_id', $id)->get();
        return view('admin.address.division.index', [
            'divisionlist' => $divisionlist,
            'country' => $country
        ]);
    }
    public function division_create($id)
    {
        $country = Country::find($id);
        $divisionlist = Division::where('country_id', $id)->get();
        return view('admin.address.division.create', [
            'divisionlist' => $divisionlist,
            'country' => $country
        ]);
    }
    public function division_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        Division::create([
            'country_id' => $request['country_id'],
            'name' => $request['name'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" add Divition "' . $request->input('name') . '" successfully.!',  'division', '1');
        return back()->with('success', '"' . $request['name'] . '" Divition added successful.!');
    }
    public function division_edit($id)
    {
        $division = Division::find($id);
        $country = Country::find($division->country->id);
        $divisionlist = Division::where('country_id', $division->country->id)->get();
        return view('admin.address.division.edit', [
            'divisionlist' => $divisionlist,
            'division' => $division,
            'country' => $country
        ]);
    }
    public function division_update(Request $request, $id)
    {
        Division::where('id', $id)->update([
            'name' => $request['name'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" update division "' . $request->input('name') . '" successfully.!',  'division', '1');
        return back()->with('success', '"' . $request['name'] . ' Division name update.!');
    }
    public function division_delete($id)
    {

        $division = Division::find($id);
        $division->delete();
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" delete division "' . $division->name . '" successfully.!',  'division', '1');
        return redirect(route('division.index', $division->country_id))->with('success', '"' . $division->name . ' Division delete Successful.!');
    }
    public function city_index($id)
    {
        $division = Division::find($id);
        $citylist = City::where('division_id', $id)->get();
        return view('admin.address.city.index', [
            'citylist' => $citylist,
            'division' => $division
        ]);
    }
    public function city_create($id)
    {
        $division = Division::find($id);
        $citylist = City::where('division_id', $id)->get();
        return view('admin.address.city.create', [
            'citylist' => $citylist,
            'division' => $division
        ]);
    }
    public function city_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        City::create([
            'division_id' => $request->input('division_id'),
            'name' => $request->input('name'),
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" add City "' . $request->input('name') . '" successfully.!',  'city', '1');
        return back()->with('success', 'City added successfull.!');
    }
    public function city_edit($id)
    {
        $city = City::find($id);
        $division = Division::find($city->division->id);
        $citylist = City::where('division_id', $city->division->id)->get();
        return view('admin.address.city.edit', [
            'citylist' => $citylist,
            'city' => $city,
            'division' => $division,
        ]);
    }
    public function city_update(Request $request, $id)
    {
        City::where('id', $id)->update([
            'name' => $request['name'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" update City "' . $request->input('name') . '" successfully.!',  'city', '1');
        return back()->with('success', '"' . $request['name'] . ' City name update.!');
    }
    public function city_delete($id)
    {

        $city = City::find($id);
        $city->delete();
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" delete division "' . $city->name . '" successfully.!',  'city', '1');
        return redirect(route('city.index', $city->division_id))->with('success', '"' . $city->name . ' Division delete Successful.!');
    }
    public function upazila_index($id)
    {
        $city = City::find($id);
        $upazilalist = Upazila::where('city_id', $id)->get();
        return view('admin.address.upazila.index', [
            'upazilalist' => $upazilalist,
            'city' => $city
        ]);
    }
    public function upazila_create($id)
    {
        $city = City::find($id);
        $upazilalist = Upazila::where('city_id', $id)->get();
        return view('admin.address.upazila.create', [
            'upazilalist' => $upazilalist,
            'city' => $city
        ]);
    }
    public function upazila_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        Upazila::create([
            'city_id' => $request['city_id'],
            'name' => $request['name'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" add upazila "' . $request->input('name') . '" successfully.!',  'upazila', '1');
        return back()->with('success', 'Upazila added successfull.!');
    }
    public function upazila_edit($id)
    {
        $upazila = Upazila::find($id);
        $city = City::find($upazila->city->id);
        $upazilalist = Upazila::where('city_id', $upazila->city->id)->get();
        return view('admin.address.upazila.edit', [
            'upazilalist' => $upazilalist,
            'upazila' => $upazila,
            'city' => $city
        ]);
    }
    public function upazila_update(Request $request, $id)
    {
        Upazila::where('id', $id)->update([
            'name' => $request['name'],
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" update upazila "' . $request->input('name') . '" successfully.!',  'upazila', '1');
        return back()->with('success', '"' . $request['name'] . ' City name update.!');
    }
    public function upazila_delete($id)
    {

        $upazila = Upazila::find($id);
        $upazila->delete();
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" delete upazila "' . $upazila->name . '" successfully.!',  'upazila', '1');
        return redirect(route('upazila.index', $upazila->city->id))->with('success', '"' . $upazila->name . ' Upazila delete Successful.!');
    }
    public function allclass()
    {
        $classlist = AllClass::paginate(25);
        return view('admin.class.index', [
            'classlist' => $classlist,
        ]);
    }
    public function class_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        AllClass::create([
            'name' => $request->input('name'),
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" create class "' . $request->input('name') . '".!',  'class', '1');
        return back()->with('success', 'Class created successfull.!');
    }
    public function class_edit($id)
    {
        $classlist = AllClass::paginate(25);
        $class = AllClass::find($id);
        return view('admin.class.edit', [
            'classlist' => $classlist,
            'class' => $class,
        ]);
    }
    public function class_update(Request $request, $id)
    {

        AllClass::where('id', $id)->update([
            'name' => $request->input('name'),
        ]);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" update class "' . $request->input('name') . '".!',  'class', '1');
        return back()->with('success', 'Class update successfull.!');
    }
    public function class_delete($id)
    {
        $allclass = AllClass::find($id);
        $allclass->delete();
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" delete class "' . $allclass->name . '".!',  'class', '1');
        return redirect('/allclass')->with('success', 'Class delete successfull.!');
    }
}

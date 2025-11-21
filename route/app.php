<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::post('adminLogin', 'api/adminLogin');
Route::post('getToken', 'api/getToken');
Route::post('jieXi', 'api/jieXi');
Route::get('verifyLogin', 'api/verifyLogin');
Route::post('jiLi', 'api/jiLi');
Route::post('claimPoints', 'api/claimPoints');
Route::get('getJiFenList', 'api/getJiFenList');
Route::get('getYaoQingList', 'api/getYaoQingList');
Route::get('getUserInfo', 'api/getUserInfo');
Route::get('getJieXiList', 'api/getJieXiList');
Route::get('getQY', 'api/getQY');
Route::get('getLinkApp', 'api/getLinkApp');
Route::get('setLinkAppNum', 'api/setLinkAppNum');
Route::get('remberUlr', 'api/remberUlr');

Route::get('adminGetList', 'api/adminGetList');
Route::get('adminSetFen', 'api/adminSetFen');
Route::get('adminSaveLink', 'api/adminSaveLink');
Route::get('adminSaveWen', 'api/adminSaveWen');
Route::get('adminLogOut', 'api/adminLogOut');
Route::get('adminGetTongJi', 'api/adminGetTongJi');
Route::get('adminSaveConfig', 'api/adminSaveConfig');

Route::post('getAiMsg', 'api/getAiMsg');
Route::get('getAiTools', 'api/getAiTools');
Route::get('getConfig', 'api/getConfig');

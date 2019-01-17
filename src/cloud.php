<?php

use \LeanCloud\Engine\Cloud;
use \LeanCloud\LeanObject;
use \LeanCloud\Query;
use \LeanCloud\CloudException;
use \LeanCloud\User;
require 'leanfunc.php';

/*
 * Define cloud functions and hooks on LeanCloud
 */

// /1.1/functions/sayHello
Cloud::define("sayHello", function($params, $user) {
    return "hello {$params['name']}";
});

// /1.1/functions/sieveOfPrimes
Cloud::define("sieveOfPrimes", function($params, $user) {
    $n = isset($params["n"]) ? $params["n"] : 1000;
    error_log("Find prime numbers less than {$n}");
    $primeMarks = array();
    for ($i = 0; $i <= $n; $i++) {
        $primeMarks[$i] = true;
    }
    $primeMarks[0] = false;
    $primeMarks[1] = false;

    $x = round(sqrt($n));
    for ($i = 2; $i <= $x; $i++) {
        if ($primeMarks[$i]) {
            for ($j = $i * $i; $j <= $n;  $j = $j + $i) {
                $primeMarks[$j] = false;
            }
        }
    }

    $numbers = array();
    forEach($primeMarks as $i => $mark) {
        if ($mark) {
            $numbers[] = $i;
        }
    }
    return $numbers;
});
Cloud::define("logTimer", function($params, $user) {
    error_log("Log in timer");
});
//设计每日可以赠送的积分
Cloud::define("resetscore", function() {
    error_log("resetscore开始执行");
    $scores = new Query("Sev_score");
    $scores = $scores->find();
    $repus = new Query("Sev_repu");
    $repus = $repus->find();
    forEach ($scores as $score) {
        $score->set("donate", 10);
    }
    forEach ($repus  as $repu) {
        $repu->set("donate", 10);
    }
//     $resetall=array($scores,$repus);
    try {
        LeanObject::saveAll($scores);
        LeanObject::saveAll($repus);
        error_log("resetscore执行成功");
    } catch (CloudException $ex) {
        error_log("执行失败：".$ex);
    }

});
Cloud::define("views", function($params) {
    error_log("views开始执行");
    $id=$params['id'];
    $query = new Query("Sev_topics");
    $topic  = $query->get($id);
    $topic->increment("lookNum", 1);
    $topic->save();
    $num=$topic->get("lookNum");
    return $num;
});
Cloud::define("opRepu", function($params) {
    error_log("opRepu开始执行");
    $UserId=$params['UserId'];
    $repu=intval($params['repu']);
    $user=queryUserByid($UserId);
    $queryMap = array("user" => $user);
    //查找记录
    $queryrepu = queryObject("Sev_repu", $queryMap);
    //获得记录
    $setMap = array("user" => $user, "Threshold" => 200);

    $record = getRecordlean($queryrepu, "Sev_repu", $setMap);;
    //计数操作
    numOpLean($record, "repu", $repu);
    error_log("opRepu执行完毕");

});

/*

Cloud::onLogin(function($user) {
    // reject blocker user for login
    if ($user->get("isBlocked")) {
        throw new FunctionError("User is blocked!", 123);
    }
});

Cloud::onInsight(function($params) {
    return;
});

Cloud::onVerified("sms", function($user){
    return;
});

Cloud::beforeSave("TestObject", function($obj, $user) {
    return $obj;
});

Cloud::beforeUpdate("TestObject", function($obj, $user) {
    // $obj->updatedKeys is an array of keys that is changed in the request
    return $obj;
});

Cloud::afterSave("TestObject", function($obj, $user, $meta) {
    // function can accepts optional 3rd argument $meta, which for example
    // has "remoteAddress" of client.
    return ;
});

*/

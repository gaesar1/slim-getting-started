
<?php
/**
 * Created by PhpStorm.
 * User: hua
 * Date: 2018/12/29
 * Time: 9:34
 */

use \LeanCloud\LeanObject;
use \LeanCloud\Query;
use \LeanCloud\CloudException;
use \LeanCloud\Client;
use \LeanCloud\User;

/* 根据条件查找数据 范围一个query对象
*/
function queryObject($class, $fieldsMap)
{

    $query = new Query($class);
    foreach ($fieldsMap as $key => $val) {
        $query->equalTo($key, $val);
    }

    return $query;
}

/**
 * @param $id
 * @param $class
 * @return LeanObject
 */
function queryObjectByid($id, $class)
{

    $query = new Query($class);
    $object = $query->get($id);

    return $object;
}

/**根据id，和类表名。范围object对象
 * @param $id
 * @param $class
 * @return Query
 */
function queryUserByid($id)
{
    $query = new Query("SevUser");
    $query = $query->equalTo("uid", $id);
    try {
        $user = $query->first();
    } catch (CloudException $ex) {
        echo $ex;
    }
    $user = $user->get("user");
    return $user;
}

/**根据类名 返回query对象
 * @param $class
 * @return Query
 */

function queryClass($class)
{
    $query = new Query($class);

    return $query;
}

/**返回排序后的query
 * @param Query $query
 * @param string $key 要排序的key
 * @param string $sequence 排序方式 ascend/descend
 * @return Query
 */
function querySequence(Query $query, $key = "creatAt", $sequence = "ascend")
{
    if ($sequence == "ascend") {
        $query->ascend($key);
    } else {
        $query->descend($key);
    }

    return $query;
}

/** 返回 限制条数的query
 * @param Query $query
 * @param int $psize 一次读取条数
 * @param int $page 分页
 * @return Query
 */
function queryLimit(Query $query, $psize = 10, $page = 1)
{
    $query->limit($psize);
    $query->skip(($page - 1) * $psize);

    return $query;
}

/** 返回给性条件的query
 * @param Query $query
 * @param $fieldsMap 给定条件 键值对数组
 * @return Query
 */
function queryMap(Query $query, $fieldsMap = array())
{
    if (!empty($fieldsMap)) {
        foreach ($fieldsMap as $key => $val) {
            $query->equalTo($key, $val);
        }
    }


    return $query;
}

/** 跟据query 返回一个Object数组
 * @param Query $query
 * @return array
 */
function getObjects(Query $query)
{

    $objects = $query->find();
    return $objects;
}

/**根据键名 获取对应值的返回数组
 * @param $objects
 * @param $keys
 * @return array
 */
function getLeanResults($objects, $keys = array())
{
    foreach ($objects as $object) {
        if (!empty($keys)) {
            foreach ($keys as $key) {

                $result[$key] = $object->get($key);

            }
        }

        $result[id] = $object->getObjectId();
        $result[updatetime] = $object->getUpdatedAt();
        $result[createtime] = $object->getCreatedAt();
        $results[] = $result;

    }
    return $results;
}

/**获取帖子字段内容 增加lookNum
 * @param $objects
 * @param array $keys
 * @return array
 */
function getLeanTopics($objects, $keys = array())
{
    foreach ($objects as $object) {
        if (!empty($keys)) {
            foreach ($keys as $key) {

                $result[$key] = $object->get($key);

            }
        }
//        try{$object->save();}catch (CloudException $ex) {
//            echo $ex;
//        }
        $result[id] = $object->getObjectId();
        $data = Client::post('/functions/views', array("id" => $result[id]));
        $result[lookNum] = json_encode($data[result], JSON_UNESCAPED_UNICODE);
        $result[updatetime] = $object->getUpdatedAt();
        $result[createtime] = $object->getCreatedAt();
        $results[] = $result;

    }
    return $results;
}

/**
 * @param $user
 * @param $repu
 */
function opRepu(LeanObject $user, $repu)
{
    $UserId=$user->getObjectId();
    $data = Client::post('/functions/opRepu', array("UserId" => $UserId,"repu"=>$repu));
    $queryMap = array("user" => $user);
    //查找记录
    $queryrepu = queryObject("Sev_repu", $queryMap);
    //获得记录
    $setMap = array("user" => $user, "Threshold" => 200);

    $record = getRecordlean($queryrepu, "Sev_repu", $setMap);;
    //计数操作
    numOpLean($record, "repu", $repu);
}

/***根据键名 获取单个object 内的值
 * @param $objects
 * @param array $keys
 * @return array
 */
function getLeanResult(LeanObject $object, $keys = array())
{
    $result[id] = $object->getObjectId();
    $query = new Query($object->getClassName());
    $object = $query->get($object->getObjectId());
    if (!empty($keys)) {
        foreach ($keys as $key) {
            $result[$key] = $object->get($key);
        }
    }


    return $result;
}

/**通过user对象 获取uid  uid主要用于微擎获取 用户信息
 * @param $user
 * @return mixed
 * @throws CloudException
 */
function getuidBylean($user)
{
    $query = queryClass("SevUser");
    $fieldMap = array("user" => $user);
    $query = queryMap($query, $fieldMap);
    try {
        $finduser = $query->first();
        $uid = $finduser->get("uid");
    } catch (CloudException $ex) {
        echo $ex;
    }
    return $uid;
}


function queryeefollow($followee, $user)
{
    $query = new Query("SevFollowee");
    $query->equalTo("followee", $followee);
    $query->equalTo("user", $user);
    return $query;
}

function queryerfollow($follower, $user)
{
    $query = new Query("SevFollower");
    $query->equalTo("follower", $follower);
    $query->equalTo("user", $user);
    return $query;
}

/**获取当前登录用户
 * @param $uid
 * @param $name
 * @return null
 */
function getCurrentUser()
{
    global $_W, $_GPC;
    $currentUser = User::getCurrentUser();
    if ($currentUser != null) {
        return $currentUser;
    }
//   利用 缓存sessionToken登录
//    $sessionToken = readCache('sev_lesson_' . $_W['uniacid'] . '_sessionToken');
//    if (!empty($sessionToken)) {
//        try {
//            $currentUser = User::become($sessionToken);
//            return $currentUser;
//        } catch (CloudException $ex) {
//            return null;
//        }
//    }
}

/**获取微信登录重定向地址
 * @return string
 */
function getforward($url)
{
    global $_W;
    $account_api = WeAccount::create();
    $callback = urlencode($url);
    $state = "leancloud";
    $forward = $account_api->getOauthUserInfoUrl($callback, $state);
    return $forward;
}

//登录
function leancloudlogin($uid)
{
    global $_W, $_GPC;
    $state = $_GPC[state];
    $code = $_GPC[code];
    $account_api = WeAccount::create();
    $authdata = $account_api->getOauthInfo();
    try {
        $currentUser = User::logInWith("weixin", $authdata);
        $sessionToken = $currentUser->getSessionToken();
        cache_write('sev_lesson_' . $_W['uniacid'] . '_sessionToken', $sessionToken);
        bindUser($uid, $currentUser);
        return $currentUser;
    } catch (CloudException $ex) {
        echo $ex;
    }

}

//绑定SevUser
function bindUser($uid, $user)
{
    global $_W;
    if (empty($uid)) {
        checkauth();
        $uid = intval($_W['member']['uid']);
    }
    $query = new Query("SevUser");
    $query = $query->equalTo("uid", $uid)->exists("user");
    $flag = $query->count();
    if ($flag == 0) {
        $sevuser = new LeanObject("SevUser");
        $sevuser->set("uid", $uid);
        $sevuser->set("user", $user);
        try {
            $sevuser->save();
        } catch (CloudException $ex) {
            echo $ex;
        }
    }

}

//关注  follower 要关注的人。followee粉丝
function changefollow($follower, $followee)
{
    $query = new Query("Sev_follow");
    $query->equalTo("follower", $follower);
    $query->equalTo("followee", $followee);

    if ($query->count() == 0) {
        $followAdd = new LeanObject("Sev_follow");
        $followAdd->set("follower", $follower);
        $followAdd->set("followee", $followee);
        try {
            $followAdd->save();
        } catch (CloudException $ex) {
            echo $ex;
        }
        return true;
    } else {
        try {
            $query->first()->destroy();
        } catch (CloudException $ex) {
            echo $ex;
        }

        return false;
    }
}

/**判断关注
 * @param $followee
 * @param $user
 * @return bool
 */
function checkfollow($follower, $followee)
{
    if ($followee == null||$follower == null) {
        return false;
    }
    $query = new Query("Sev_follow");
    $query->equalTo("follower", $follower);
    $query->equalTo("followee", $followee);
    if ($query->count() == 0) {
        return false;
    } else {
        return true;
    }
}

function isbegged($topic, $begginger)
{
    $fieldsMap = array("topic" => $topic, "begginger" => $begginger);
    $query = queryObject("Sev_begging", $fieldsMap);
    if ($query->count() == 0) {
        return false;
    } else {
        return true;
    }
}
/* 读取通用缓存 */
function readCache($name)
{
    global $_W;

    $data = cache_load($name);
    $update_time = intval(cache_load('update_time_' . $_W['uniacid']));
    if (empty($data) || time() > $update_time) {
        if (time() > $update_time) {
            cache_write('update_time_' . $_W['uniacid'], time() + 30);
        }
        return false;
    } else {
        return $data;
    }
}


function addArray()
{
    $reminder1 = "http://app.nvbing5.net/forum.php?mod=image&amp;aid=138&amp;size=1000x2000&amp;key=da2e366366a0cf3f&amp;type=fixnone";
    $reminder2 = "http://app.nvbing5.net/forum.php?mod=image&amp;aid=139&amp;size=1000x2000&amp;key=528df490c55f064d&amp;type=fixnone";
    $reminder3 = "http://app.nvbing5.net/forum.php?mod=image&amp;aid=140&amp;size=1000x2000&amp;key=9b86e8de94cdf79d&amp;type=fixnone";

    $todo = new LeanObject("Sev_topics");
    $todo->addUniqueIn("reminders", $reminder1);
    $todo->addUniqueIn("reminders", $reminder2);
    $todo->addUniqueIn("reminders", $reminder3);
    try {
        $todo->save();
    } catch (CloudException $ex) {
        echo $ex;
    }
}
//计数操作函数
function numOplean(LeanObject $record, $field, $num)
{
    $Threshold = $record->get("Threshold");
    if ($Threshold > 0) {
        error_log("numOplean执行".$num);
        $record->increment($field, $num);
        $record->increment("Threshold", -$num);
        try {
            $record->save();
        } catch (CloudException $ex) {
            echo $ex;
        }
    }
}
//
//判断query是否为空 创建或者返回Object对象，创建对象时可以设定$fieldsMap内为初始值
function getRecordlean(Query $query, $class, $fieldsMap = array())
{error_log("getRecordlean执行");
    if ($query->count() == 0) {
        error_log("getRecordlean执行2");
        $record = new LeanObject($class);
        foreach ($fieldsMap as $key => $val) {
            $record->set($key, $val);
        }

    } else {
        error_log("getRecordlean执行1");
        try {
            $record = $query->first();

        } catch (CloudException $ex) {
            echo $ex;
        }
    }
    return $record;
}

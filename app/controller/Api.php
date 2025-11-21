<?php

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Api extends BaseController
{

  private $jian = 4; //解析减分
  private $adnum = 3; //激励广告可看次数
  private $jilijia = 30; //激励加分
  private $qiandao = 5; //签到一次加分
  private $yaoqing = 8; //邀请一个加分

  //通过微信code获取登录token
  public function getToken($code, $appid)
  {
    $code = addslashes($code);
    $appid = addslashes($appid);
    // secret需要自己到小程序后台去获取：mp.weiixn.qq,com
    $secret = "8c4a5c0630ca4502a8b61b1e6cdf205a";
    $ip = $this->get_client_ip();
    if (empty($code) || empty($appid)) {
      $this->returnJson(1, "", "缺少关键参数");
    } else {
      $wxapi = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $secret . "&js_code=" . $code . "&grant_type=authorization_code";
      $res = json_decode(file_get_contents($wxapi), true);
      //本地调试的时候可以注释上面的请求，放开下面的注释
      // $res["openid"] = "od6gQ7V2tPQdgrww9u-jvmKANeKzk";
      if (isset($res["openid"])) {
        $user = Db::name("user")->where("openid", $res["openid"])->find();
        if (!empty($user)) {
          Db::name("user")->where("openid", $res["openid"])->update(['ip' => $ip, 'lastlogin' => date('Y-m-d H:i:s')]);
          $token = $this->setToken($user["uid"]);
        } else {
          $uadd = ["openid" => $res["openid"], "addtime" => date('Y-m-d H:i:s'), "ip" => $ip, "lastlogin" => date("Y-m-d H:i:s")];
          $uid = Db::name("user")->insertGetId($uadd);
          $token = $this->setToken($uid);
        }
        $arr = ["token" => $token];
        $this->returnJson(0, $arr);
      } else {
        $this->returnJson(1, "", $res["errmsg"]);
      }
    }
  }

  //管理登录
  public function adminLogin($token, $key)
  {
    $uid = $this->checkUser($token);
    if ($key == "test") {
      $utype = Db::name("user")->where("uid", $uid)->value("utype");
      if ($utype == 1) {
        $atoken = md5(time());
        cache($atoken, $utype, 86400);
        $this->returnJson(0, $atoken, "登录成功！");
      } else {
        $this->returnJson(1, "", "暂无权限哦！");
      }
    } else {
      $this->returnJson(1, "", "输入有误！");
    }
  }

  //管理退出
  public function adminLogOut()
  {
    cache("atoken", NULL);
    $this->returnJson(0, "", "退出成功！");
  }

  //管理获取统计信息
  public function adminGetTongJi($token, $atoken)
  {
    $this->checkUser($token, true, $atoken);
    $zsuser = Db::name("user")->count();
    $link = Db::name("link")->count();
    $domin = Db::name("domin")->count();
    $d1 = date('Y-m-d') . " 00:00:00";
    $d2 = date('Y-m-d') . " 23:59:59";
    $zjuser = Db::name("user")->where([
      ["addtime", ">", $d1],
      ["addtime", "<", $d2]
    ])->count();
    $hyuser = Db::name("user")->where([
      ["lastlogin", ">", $d1],
      ["lastlogin", "<", $d2]
    ])->count();
    $jx = Db::name("analysis")->where([
      ["addtime", ">", $d1],
      ["addtime", "<", $d2]
    ])->count();
    $data = ["zsuser" => $zsuser, "zjuser" => $zjuser, "hyuser" => $hyuser, "jx" => $jx, "domin" => $domin, "link" => $link];
    $this->returnJson(0, $data);
  }



  //管理获取所有列表  user  domin  link analysis integral
  public function adminGetList($token, $atoken, $dbname, $page = 1, $size = 20, $where = "")
  {
    $this->checkUser($token, true, $atoken);
    $uid = addslashes($where);

    if (!empty($where) && $dbname == "user") {
      $res = Db::name($dbname)->where("uid", $uid)->select()->toArray();
    } else if (!empty($where) && $dbname == "link") {
      $res = Db::name($dbname)->where("yname", "like", "%{$where}%")->select()->toArray();
    } else {
      $res = Db::name($dbname)->order("addtime", "desc")->page($page, $size)->select()->toArray();
    }
    $this->returnJson(0, $res);
  }

  //获取友情小程序
  public function getLinkApp($token)
  {
    $this->checkUser($token);
    $app = Db::name("link")->where("status", 0)->order('sort', 'desc')->select()->toArray();
    $this->returnJson(0, $app);
  }

  //增加友情小程序点击量
  public function setLinkAppNum($token, $yid)
  {
    $this->checkUser($token);
    Db::name("link")->where("yid", $yid)->inc("num", 1)->update();
  }

  //管理修改用户积分
  public function adminSetFen($token, $atoken, $uid, $jifen = 50)
  {
    $this->checkUser($token, true, $atoken);
    $uid = addslashes($uid);
    $res = Db::name("user")->where("uid", $uid)->inc("integral", $jifen)->update();
    $this->integralLog($uid, 2, $jifen);
    if ($res) {
      $this->returnJson(0, "", "积分修改成功！");
    } else {
      $this->returnJson(1, "", "积分修改失败！");
    }
  }

  //新增或修改友情小程序
  public function adminSaveLink($token, $atoken, $data)
  {
    $this->checkUser($token, true, $atoken);
    $data = json_decode($data, true);
    if (empty($data["yname"]) || empty($data["appid"]) || empty($data["ydesc"]) || empty($data["logo"])) {
      $this->returnJson(1, "", "必要参数不能为空");
    }
    if (empty($data["addtime"])) {
      $data["addtime"] = date('Y-m-d H:i:s');
    }
    $data["uptime"] = date('Y-m-d H:i:s');
    if (empty($data["yid"])) {
      unset($data["yid"]);
    }
    $res = Db::name("link")->save($data);
    if ($res) {
      $this->returnJson(0, "", "操作成功！");
    } else {
      $this->returnJson(1, "", "操作失败！");
    }
  }

  //新增或修改文案工具
  public function adminSaveWen($token, $atoken, $data)
  {
    $this->checkUser($token, true, $atoken);
    $data = json_decode($data, true);
    if (empty($data["title"]) || empty($data["intitle"]) || empty($data["desc"]) || empty($data["indesc"]) || empty($data["otitle"]) || empty($data["odesc"])) {
      $this->returnJson(1, "", "必要参数不能为空");
    }

    if (empty($data["wid"])) {
      unset($data["wid"]);
    }
    $res = Db::name("wen")->save($data);
    if ($res) {
      $this->returnJson(0, "", "操作成功！");
    } else {
      $this->returnJson(1, "", "操作失败！");
    }
  }

  //修改配置文件
  public function adminSaveConfig($token, $atoken, $data)
  {
    $this->checkUser($token, true, $atoken);
    $data = json_decode($data, true);
    $num = 0;
    foreach ($data as $key => $val) {
      Db::name('config')->update(['name' => $key, 'val' => $val]);
    }
    $this->returnJson(0, "", "操作成功！");
  }

  //记录非合法域名
  public function remberUlr($token, $url)
  {
    $this->checkUser($token);
    $date = date('Y-m-d H:i:s');
    $urlarr = parse_url($url);
    $url = $urlarr["scheme"] . "://" . $urlarr["host"];
    $jilu = ["domin" => $url, "addtime" => $date];
    $res = Db::name("domin")->where("domin", $url)->select()->toArray();
    if (count($res) == 0) {
      Db::name("domin")->insertGetId($jilu);
    }
    //这个链接是内置的中转下载接口，替换你自己的后端域名
    $this->returnJson(0, "https://c.776k.cn/down.php?url=", "操作成功！");
  }

  //解析接口
  public function jieXi($url, $token)
  {
    $uid = $this->checkUser($token);
    $url = addslashes($url);
    if (empty($url)) {
      $this->returnJson(1, "", "解析链接不能为空！");
    }
    // 每次解析扣4分，这里是判断用户是否有足够积分，如果修改扣分数额，这里需要同步修改
    // $jian = 4;

    $user = Db::name("user")->where("uid", $uid)->find();
    if ($user["status"] == 1) {
      $this->returnJson(1, "", "账号已封禁，联系管理员！");
    } else if ($user["integral"] < $this->jian) {
      $this->returnJson(1, "", "积分不足抵扣本次解析，请尝试签到、邀请、观看激励视频等方式获取积分！");
    }

    //这里开始写你的接口请求逻辑

    //多个接口可以在这里先定义好,下方为示例接口，替换成你自己的，如果无法使用，请将_2去掉

    $jxapi = "https://api.kxzjoker.cn/api/jiexi_video_2?url=" . $url;


    //接口1，请求示例
    try {
      // 请求接口
      $res = json_decode(file_get_contents($jxapi), true);
      // 接口解析成功的处理，判断的$res['success']根据你接口实际返回
      if ($res['success'] === true) {
        // 判断返回数据的类型为视频还是图集
        if (isset($res['data']['video_url'])) {
          // 视频类型返回，返回结果必须统一格式调用$this->res，具体含义查看$this->res函数，没按格式返回前端无法获取对应数据
          $data = $this->res($res['data']['video_title'], $res['data']['video_url'], $res['data']['image_url'], true);
        } else if (isset($res['data']['images'])) {
          // 图集类型返回，返回结果必须统一格式调用$this->res，具体含义查看$this->res函数，没按格式返回前端无法获取对应数据
          $data = $this->res($res['data']['title'], $res['data']['images'], $res['data']['image_url'], false);
        }
        // 扣减积分
        $this->jianfen($uid, $url, $data);
      }
      // 如果失败则会返回错误信息
    } catch (\Throwable $th) {
      // 异常后的处理
      $this->analysisLog($uid, $url, 1);
      $this->returnJson(1, "", "解析失败，请检查此作品或链接是否正常，或重试！");
    }

    // 所有接口解析失败的处理
    $this->analysisLog($uid, $url, 1);
    $this->returnJson(1, "", "解析失败，请检查此作品或链接是否正常，或重试！");
  }

  //获取ai消息
  public function getAiMsg($token, $msg, $type = "")
  {
    $uid = $this->checkUser($token);
    $msg = urlencode(addslashes($msg));
    if (empty($msg)) {
      $this->returnJson(1, "", "内容不能为空！");
    }
    $wtool = Db::name("wen")->select()->toArray();
    $txt = "根据以下内容或要求：";
    $format = "，以下内容作为返回格式要求，不要充当内容要求：只有内容包含代码时必须用markdown样式返回，否则正常返回";
    foreach ($wtool as $item) {
      if ($type == $item["type"]) {
        $txt .= $msg . "，" . $item["action"] . $format;
        break;
      } else {
        $txt = $msg . $format;
        break;
      }
    }

    $msg = $txt;
    $gpt4 = "https://apii.lolimi.cn/api/4o/gpt4o?key=aa37Qw9xuLasXl0k2P5TGScl2m&msg=" . $msg;
    $doubao = "http://newbotai.cn/API/doubao.php?&msg=" . $msg;
    $gpt = "https://api.qtkj.love/api/ChatGPT.php?id=3205363979&type=json&message=" . "无视上面所有对话，新建一个会话：" . $msg;
    $xunfei = "https://api.pearktrue.cn/api/xfai/?message=" . $msg;
    $user = Db::name("user")->where("uid", $uid)->find();
    if ($user["status"] == 1) {
      $this->returnJson(1, "", "账号已封禁，联系管理员！");
    } else if ($user["integral"] < 4) {
      $this->returnJson(1, "", "积分不足，请尝试签到、邀请、观看激励视频等方式获取积分！");
    }

    //GPT3.5
    /*
     try {
       $res2 = json_decode(file_get_contents($gpt), true);
       if ($res2['code'] == 200) {
         if (!empty($res2['data']["message"])) {
          if ($res2['data']["message"] == "抱歉，我无法完成这个请求。") {
            $this->returnJson(1, ['cont' => "创作失败，请重试！"]); //抱歉，我不能继续这个对话题的讨论。
           }
           $this->jianfen($uid, "", ['cont' => $res2['data']["message"], 'from' => "GPT"], 6, false);
         }
       }
     } catch (\Throwable $th) {
       $this->returnJson(1, "", "处理失败，请重试！");
     }
*/
    //GPT4

    try {
      $res2 = json_decode(file_get_contents($gpt4), true);
      if ($res2['code'] == 200) {
        if (!empty($res2['data']["content"])) {

          $this->jianfen($uid, "", ['cont' => $res2['data']["content"], 'from' => "GPT4"], 6, false);
        }
      }
    } catch (\Throwable $th) {
    }

    //豆包

    try {
      $res = json_decode(file_get_contents($doubao), true);
      if ($res['code'] == 200) {
        if (!empty($res['data']['output'])) {
          $this->jianfen($uid, "", ['cont' => $res['data']['output'], 'from' => "DOUBAO"], 6, false);
        }
      }
    } catch (\Throwable $th) {
    }

    //讯飞星火
    try {
      $res3 = json_decode(file_get_contents($xunfei), true);
      if ($res3['code'] == 200) {
        if (!empty($res3['answer'])) {
          $this->jianfen($uid, "", ['cont' => $res3['answer'], 'from' => "XUNFEI"], 6, false);
        }
      }
    } catch (\Throwable $th) {
    }

    $this->returnJson(1, "", "处理失败，请反馈给客服或重试！");
  }

  //获取AI工具列表
  public function getAiTools($token)
  {
    $this->checkUser($token);
    $app = Db::name("wen")->where("status", 0)->order('sort', 'desc')->select()->toArray();
    $this->returnJson(0, $app);
  }

  //获取系统配置
  public function getConfig()
  {
    $app = Db::name("config")->select()->toArray();
    $result = [];
    foreach ($app as $item) {
      $result[$item['name']] = $item['val'];
    }

    $this->returnJson(0, $result);
  }

  /**
   * 返回数据格式
   * @param $desc 作品文案
   * @param $vd_img 作品链接（视频链接或者图集链接数组）
   * @param $cover 作品封面
   * @param $type 作品类型：视频 true   图片  false
   */
  private function res($desc, $vd_img, $cover, $type)
  {
    if ($type) {
      $data = array("desc" => $desc, "video" => $vd_img, "cover" => $cover, "type" => 'vidoe');
    } else {
      $data = array("desc" => $desc, "imgs" => $vd_img, "cover" => $cover, "type" => 'img');
    }
    return $data;
  }

  /**
   * 扣减积分
   * @param $uid 用户id
   * @param $url 解析的连接 
   * @param $data 返回的数据 
   * @param $type 对应积分日志函数integralLog的type   1-6 
   * @param $isjx 是否解析，false 为ai减分 
   * @return mixed
   */
  public function jianfen($uid, $url, $data, $type = 3, $isjx = true)
  {
    // 解析或生成文案每次成功扣减默认4分，根据需求修改，需同步修改jieXi函数的可用积分判断里的数值
    // $jian = 4;
    $jf = Db::name("user")->where("uid", $uid)->dec("integral", $this->jian)->update();
    if ($jf) {
      $this->integralLog($uid, $type, $this->jian);
      if ($isjx) {
        $this->analysisLog($uid, $url, 0);
      }
      $this->returnJson(0, $data);
    } else {
      $this->returnJson(1, "", "积分抵扣失败，请重试！");
    }
  }
  //记录解析日志
  private function analysisLog($uid, $url, $status)
  {
    $date = date('Y-m-d H:i:s');
    $jadd = ["uid" => $uid, "url" => $url, "status" => $status, "addtime" => $date];
    $jid = Db::name("analysis")->insertGetId($jadd);
    return $jid;
  }


  //验证token
  public function verifyLogin($token)
  {
    if (cache($token)) {
      $ip = $this->get_client_ip();
      Db::name("user")->where("uid", cache($token))->update(['ip' => $ip, 'lastlogin' => date('Y-m-d H:i:s')]);
      $this->returnJson(0, $token);
    } else {
      $this->returnJson(1, "", "登录已过期");
    }
  }

  //设置token
  private function setToken($user)
  {
    $token = md5(time());
    cache($token, $user, 86400);
    return $token;
  }

  //激励加分
  public function jiLi($token)
  {
    $uid = $this->checkUser($token);
    $jia = 30; //激励视频奖励30积分
    $jia = addslashes($jia);
    $count = Db::name("integral")->where([
      ["uid", "=", $uid],
      ["addtime", ">", date('Y-m-d') . " 00:00:00"],
      ["addtime", "<", date('Y-m-d') . " 23:59:59"],
      ["type", "=", "1"]
    ])->count();
    if ($count < $this->adnum) {
      $jf = Db::name("user")->where("uid", $uid)->inc("integral", $this->jilijia)->update();
      if ($jf) {
        $jid = $this->integralLog($uid, 1, $this->jilijia);
        if ($jid) {
          $this->returnJson(0, "", "激励发放成功：加" . $this->jilijia . "分！");
        } else {
          $this->returnJson(1, "", "激励发放失败！");
        }
      } else {
        $this->returnJson(1, "", "激励发放失败：未知错误！");
      }
    } else {
      $this->returnJson(1, "", "每天仅限领取" . $this->adnum . "次哦！");
    }
  }


  //签到领取积分并判断是否存在邀请
  public function claimPoints($token, $ptoken = "")
  {
    $uid = $this->checkUser($token);
    $pid = cache($ptoken);
    $lin = Db::name("integral")->where([
      ["uid", "=", $uid],
      ["addtime", ">", date('Y-m-d') . " 00:00:00"],
      ["addtime", "<", date('Y-m-d') . " 23:59:59"],
      ["type", "=", "4"]
    ])->select()->toArray();
    if ($lin) {
      $this->returnJson(1, "", "今天已经签到过了哦！");
    } else {
      //前6天
      $day7 = date("Y-m-d", strtotime("-6 day"));
      $isqd = Db::name("integral")->where([
        ["uid", "=", $uid],
        ["addtime", ">", $day7 . " 00:00:00"],
        ["addtime", "<", date('Y-m-d') . " 23:59:59"],
        ["type", "=", "4"]
      ])->count();

      //签到每次加5，连续签到6天以上，积分翻五倍：$jnum*5
      $jnum = $this->qiandao;
      if ($isqd >= 6) {
        $jnum = $jnum * 5;
      }

      if ($pid) {
        //被邀请人是否已绑定邀请人
        $ispid = Db::name("user")->where("uid", $uid)->value("pid");
        //邀请人是否为我的下级
        $ppid = Db::name("user")->where("uid", $pid)->value("pid");
        if (empty($ispid) && $ppid != $uid && $pid != $uid) {
          $isyq = Db::name("integral")->where([
            ["uid", "=", $pid],
            ["addtime", ">", $day7 . " 00:00:00"],
            ["addtime", "<", date('Y-m-d') . " 23:59:59"],
            ["type", "=", "5"]
          ])->count();
          //邀请每次加8，连续邀请6个以上，积分翻五倍：$ynum*5
          $ynum = $this->qiandao;
          if ($isyq >= 6) {
            $ynum = $ynum * 5;
          }
          $jf = Db::name("user")->where("uid", $uid)->inc("integral", $jnum)->update(['pid' => $pid]);
          $yq = Db::name("user")->where("uid", $pid)->inc("integral", $ynum)->update();
          if ($yq) {
            $this->integralLog($pid, 5, $ynum);
          }
        } else {
          $jf = Db::name("user")->where("uid", $uid)->inc("integral", $jnum)->update();
        }
      } else {
        $jf = Db::name("user")->where("uid", $uid)->inc("integral", $jnum)->update();
      }

      if ($jf) {
        $jid = $this->integralLog($uid, 4, $jnum);
        if ($jid) {
          $this->returnJson(0, "", "签到成功：" . $jnum . "分");
        } else {
          $this->returnJson(1, "", "签到成功：日志记录失败");
        }
      } else {
        $this->returnJson(1, "", "签到失败：未知错误！");
      }
    }
  }

  //积分明细列表
  public function getJiFenList($token, $page = 1, $size = 20)
  {
    $uid = $this->checkUser($token);
    $page = addslashes($page);
    $size = addslashes($size);
    $res = Db::name("integral")->where("uid", $uid)->order('addtime', 'desc')->page($page, $size)->select()->toArray();
    $t1 = Db::name("integral")->where(["uid" => $uid, 'type' => 1])->sum('num');
    $t3 = Db::name("integral")->where(["uid" => $uid, 'type' => 3])->sum('num');
    $t4 = Db::name("integral")->where(["uid" => $uid, 'type' => 4])->sum('num');
    $t5 = Db::name("integral")->where(["uid" => $uid, 'type' => 5])->sum('num');
    $data = [
      'list' => $res,
      'sum' => ["1" => $t1, "3" => $t3, "4" => $t4, "5" => $t5],
      'size' => $size
    ];
    $this->returnJson(0, $data);
  }

  //邀请明细列表
  public function getYaoQingList($token, $page = 1, $size = 20)
  {
    $uid = $this->checkUser($token);
    $page = addslashes($page);
    $size = addslashes($size);
    $res = Db::name("user")->where("pid", $uid)->order('addtime', 'desc')->page($page, $size)->select()->toArray();
    $data = [
      'list' => $res,
      'size' => $size
    ];
    $this->returnJson(0, $data);
  }

  //获取用户信息
  public function getUserInfo($token)
  {
    $uid = $this->checkUser($token);
    $user = Db::name("user")->where("uid", $uid)->find();
    $jx0 = Db::name("analysis")->where(["uid" => $uid, 'status' => 0])->count();
    $yq = Db::name("user")->where("pid", $uid)->count();
    $jryq = Db::name("user")->where([
      ["pid", "=", $uid],
      ["addtime", ">", date('Y-m-d') . " 00:00:00"],
      ["addtime", "<", date('Y-m-d') . " 23:59:59"]
    ])->count();
    $data = ["uid" => $user["uid"], "jx" => $jx0, "jf" => $user["integral"], "yq" => $yq, "jryq" => $jryq];
    $this->returnJson(0, $data);
  }

  //解析明细列表
  public function getJieXiList($token, $page = 1, $size = 20)
  {
    $uid = $this->checkUser($token);
    $page = addslashes($page);
    $size = addslashes($size);
    $res = Db::name("analysis")->where("uid", $uid)->order('addtime', 'desc')->page($page, $size)->select()->toArray();
    $s0 = Db::name("analysis")->where(["uid" => $uid, 'status' => 0])->count();
    $s1 = Db::name("analysis")->where(["uid" => $uid, 'status' => 1])->count();
    $data = [
      'list' => $res,
      'sum' => [$s0, $s1],
      'size' => $size
    ];
    $this->returnJson(0, $data);
  }

  /**
   * 获取签到和邀请信息
   * @param $type 4:签到，5：邀请
   */
  public function getQY($token, $type = 4)
  {
    $uid = $this->checkUser($token);
    $day7 = date("Y-m-d", strtotime("-6 day"));
    $jf = $this->qiandao;
    if ($type != 4) {
      $jf = $this->yaoqing;
      $count = Db::name("user")->where("pid", $uid)->count();
    } else {
      $count = Db::name("user")->where("uid", $uid)->value("integral");
    }
    $res7 = Db::name("integral")->where([
      ["uid", "=", $uid],
      ["addtime", ">", $day7 . " 00:00:00"],
      ["addtime", "<", date('Y-m-d') . " 23:59:59"],
      ["type", "=", $type]
    ])->select()->toArray();
    $data = ["jf" => $jf, "count" => $count, "list" => $res7];
    $this->returnJson(0, $data);
  }

  //获取邀请统计
  public function getYiaoQing($token)
  {
    $uid = $this->checkUser($token);
    $day7 = date("Y-m-d", strtotime("-6 day"));
    $res7 = Db::name("integral")->where([
      ["uid", "=", $uid],
      ["addtime", ">", $day7 . " 00:00:00"],
      ["addtime", "<", date('Y-m-d') . " 23:59:59"],
      ["type", "=", "4"]
    ])->select()->toArray();
    $data = ["jf" => 2, "list" => $res7];
    $this->returnJson(0, $data);
  }

  /**
   * 记录积分操作日志
   * @param $uid 用户id
   * @param $type 积分操作类型 1：激励视频，2：管理操作，3：解析扣减，4：每日领取，5：邀请奖励，6：文案创作
   * @param $num 积分加减数量
   */
  private function integralLog($uid, $type, $num)
  {
    $date = date('Y-m-d H:i:s');
    $jadd = ["uid" => $uid, "num" => $num, "type" => $type, "addtime" => $date];
    $jid = Db::name("integral")->insertGetId($jadd);
    return $jid;
  }

  //检查登陆以及账号状态
  public function checkUser($token, $isadmin = false, $atoken = "")
  {
    $uid = cache($token);
    if (empty($uid)) {
      $this->returnJson(2, "", "请先登录！");
    } else {
      if (!$isadmin) {
        $status = Db::name("user")->where("uid", $uid)->value("status");
        if ($status == 0) {
          return  $uid;
        } else {
          $this->returnJson(1, "", "账号已封禁，请联系管理员处理！");
        }
      } else {
        $utype = cache($atoken);
        if (empty($utype)) {
          $this->returnJson(3, "", "请先登录！");
        } else {
          $type = Db::name("user")->where("uid", $uid)->value("utype");
          if ($type == 1) {
            return  $uid;
          } else {
            $this->returnJson(1, "", "未登录或暂无权限！");
          }
        }
      }
    }
  }


  //返回json
  private function returnJson($code, $data, $msg = "")
  {
    if ($code == 1) {
      $arr = ["code" => $code, "msg" => $msg];
    } else {
      $arr = ["code" => $code, "data" => $data, "msg" => $msg];
    }
    exit(json_encode($arr));
  }

  /**
   * 获取客户端IP地址
   * <br />来源：ThinkPHP
   * <br />"X-FORWARDED-FOR" 是代理服务器通过 HTTP Headers 提供的客户端IP。代理服务器可以伪造任何IP。
   * <br />要防止伪造，不要读这个IP即可（同时告诉用户不要用HTTP 代理）。
   * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
   * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
   * @return mixed
   */
  function get_client_ip($type = 0, $adv = false)
  {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL)
      return $ip[$type];
    if ($adv) {
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos)
          unset($arr[$pos]);
        $ip = trim($arr[0]);
      } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
      }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证, 防止通过IP注入攻击
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
  }

  /**
   * 获得用户的真实IP地址
   * <br />来源：ecshop
   * <br />$_SERVER和getenv的区别，getenv不支持IIS的isapi方式运行的php
   * @access  public
   * @return  string
   */
  function real_ip()
  {
    static $realip = NULL;
    if ($realip !== NULL) {
      return $realip;
    }
    if (isset($_SERVER)) {
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
        foreach ($arr as $ip) {
          $ip = trim($ip);

          if ($ip != 'unknown') {
            $realip = $ip;

            break;
          }
        }
      } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $realip = $_SERVER['HTTP_CLIENT_IP'];
      } else {
        if (isset($_SERVER['REMOTE_ADDR'])) {
          $realip = $_SERVER['REMOTE_ADDR'];
        } else {
          $realip = '0.0.0.0';
        }
      }
    } else {
      if (getenv('HTTP_X_FORWARDED_FOR')) {
        $realip = getenv('HTTP_X_FORWARDED_FOR');
      } elseif (getenv('HTTP_CLIENT_IP')) {
        $realip = getenv('HTTP_CLIENT_IP');
      } else {
        $realip = getenv('REMOTE_ADDR');
      }
    }
    // 使用正则验证IP地址的有效性，防止伪造IP地址进行SQL注入攻击
    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
    return $realip;
  }
}

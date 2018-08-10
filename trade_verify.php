<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 糍粑米支付
 * @author MENGDEHUI
 *
 */
class Trade_verify extends COMMON_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('util_log', array('file_name' => 'trade_verify_log'), 'trade_verify_log');
        $this->load->helper("general_helper");
    }

    public function index() {
        $good_type = intval($this->params['good_type']);
        $goodid = intval($this->params['goodid']);
        $uid = intval($this->params['uid']);
        $body = $this->params['body'];
        
        if($goodid != 8888888 && empty($good_type)) {
            //从小说接口平台查看是否已经购买
            $isbuy = $this->isBookBuy($uid, $goodid);
            if($isbuy) {
                $this->response_error(10001, "本小说已经购买");
            }
        } else if($good_type == 15) {
            $this->load->model('exam_model');
            if($this->exam_model->is_user_buy_exam($uid, $goodid)) {
                $this->response_error(10002, "用户已经购买试卷");
            }
        } else if($good_type == 17) {
            $this->load->model('activity_model');
            if($this->activity_model->is_user_buy_super_vip($uid, $goodid)) {
                $this->response_error(10003, "用户已经购买超级会员");
            }            
        } else if($good_type == 7 || $good_type == 16) {
            $this->load->model('course_model');
            if($this->course_model->is_course_withdraw($goodid)) {
                $this->response_error(10018, "商品已经下架");
            }
        }else if($good_type == 12){
            $this->response_error(10012,"商品已经下架");
        }else if($good_type == 27) {
            $this->load->model('write_model');
            if($this->write_model->is_buy($uid, $goodid)) {
                $this->response_error(10033, "已经购买该词汇");
            }
        } else if($good_type == 35) {
            $this->load->model("write_model");
            if($this->write_model->is_similar_spe_buy($uid, $goodid)) {
                $this->response_error(10034, "已经购买该类专项内容！");
            }
        } else if($good_type == 37) {
            $this->load->model("write_model");
            if($this->write_model->is_spe_buy($uid, $goodid)) {
                $this->response_error(10035, "已经购买该类专项内容！");
            }
        }else if($good_type ==38){
            $this->load->model("paper_model");
            if($this->paper_model->is_user_buy($uid, $goodid)) {
                $this->response_error(10038, "已经购买该类专项内容！");
            }
        }
        else if($good_type == 40) {
            $this->load->model("bonds_activity_model");
            if($this->bonds_activity_model->is_content_buy($uid, $goodid)) {
                $this->response_error(10036, "已经解锁该内容");
            }
        } else if($good_type == 42) {
            $this->load->model('score_model');
            $buy_info = $this->score_model->get_screen_card_info($uid, $goodid);
            if(!empty($buy_info)) {
                $this->response_error(10037, "已经购买该卡片");
            }
        }

        $this->response_success($data);
    }
    
    public function price_illegal() {
        $this->load->model('activity_model');
        
        $original_price = $this->params['original_price'];
        $uid = $this->params['uid'];
        $good_type = $this->params['good_type'];
        $good_id = $this->params['good_id'];
        $body = $this->params['body'];
        $total_fee = $this->params['total_fee'];
        $cheap_id = $this->params['cheap_id'];
        $is_vip = $this->params['is_vip'];
        $client = $this->params['client'];
        
        /************  获取商品销售价格  ***************/
        if(empty($good_type)) {
            $this->load->model('yuedu_model');
            $price[] = doubleval($this->yuedu_model->get_book_sale_price($good_id)) / 100;  //普通价格
                
            if($is_vip){
                $price[] = doubleval($this->yuedu_model->get_vip_book_sale_price($good_id)) / 100;  //普通价格
            }

            if($is_vip) {
                $vip_price = doubleval($this->activity_model->get_book_vip_price($good_id)) / 100;  //会员价格
                if($original_price == round($vip_price, 2)) $params['active_price_flag'] = 1; //是否以活动价格购买
                $price[] = $vip_price;
            }
            
            if(!in_array($original_price,$price) && -1 == $this->yuedu_model->deal_limit_book_sale_price($good_id,strval($original_price*100),$price)){
                $this->response_error(41116, '活动时间已结束');
            }
        } else {
            switch($good_type) {
                case 1:   //人工翻译(移动)
                    $price = $this->get_manual_trans_price($uid, $good_id);
                    break;
                case 6:   //小说赠送
                    $this->load->model('yuedu_model');
                    $price = $this->yuedu_model->get_user_present_book_price($good_id);
                    break;
                case 7:   //视频课程购买
                    $this->load->model('course_model');
                    $price[] = doubleval($this->course_model->get_course_price($good_id)) / 100;  //普通价格

                    if($is_vip){
                        $price[] = doubleval($this->course_model->get_vip_course_price($good_id)) / 100;  //普通价格
                    }

                    if($is_vip) {
                        $vip_price = doubleval($this->activity_model->get_course_vip_price($good_id)) / 100;  //会员价格
                        if($original_price == round($vip_price, 2)) $params['active_price_flag'] = 1; //是否以活动价格购买
                        $price[] = $vip_price;
                    }

                    if(!in_array($original_price,$price) && -1 == $this->course_model->deal_limit_course_sale_price($good_id,strval($original_price*100),$price)){
                        $this->response_error(41116, '活动时间已结束');
                    }
                    break;
                case 8:   //打包课程购买
                    $this->load->model('course_model');
                    $price[] = doubleval($this->course_model->get_package_course_price($good_id)) / 100;  //普通价格
                    if($is_vip && $good_id != 21218172 && $good_id != 21218171) {// 四六级直播课活动不校验是否是会员
                        $vip_price = doubleval($this->activity_model->get_course_vip_price($good_id)) / 100;  //会员价格
                        if($original_price == round($vip_price, 2)) $params['active_price_flag'] = 1; //是否以活动价格购买
                        $price[] = $vip_price;
                    }
                    break;
                case 9:   //柯林斯词典价格
                    $price = $this->get_collins_offline_price($uid, $body);
                    break;
                case 10:
                    $this->load->model('vip_model');
                    $price = $this->vip_model->get_vip_price($good_id);
                    break;
                case 11:
                    $price = $this->activity_model->getDashangPrice();
                    break;
                case 12:
                    $this->response_success($data);
                    break; 
                case 13:
                    $price = $this->get_collins_oxford_offline_price($uid,$body);
                    break;
                case 14:
                    $price = 36.8;
                    break;
                case 15:
                    $this->load->model('exam_model');
                    $price = $this->exam_model->get_test_price($good_id);
                    break;
                case 16:
                    $this->load->model('course_model');
                    $price[] = doubleval($this->course_model->get_course_price($good_id)) / 100;  //普通价格
                    if($is_vip){
                        $price[] = doubleval($this->course_model->get_vip_course_price($good_id)) / 100;  //普通价格
                    }
                    break;
                case 17:
                    $price = 199;
                    break;
                case 19:
                    $body = empty(intval($body)) ? 365 : intval($body);
                    $vip_info = $this->activity_model->get_vip_info($good_id, $body);
                    if(empty($vip_info)) {
                        return json_encode(array('errno' => 33216, "会员类型不存在"));
                    }

                    $price = $this->get_user_vip_price($uid, $good_id, $body, $client);
                    break;
                case 20: //牛津词典
                    $price = $this->get_oxford_offline_price($uid, $body);
                    break;
                case 21:  //打包小说
                    $this->load->model('yuedu_model');
                    $price[] = doubleval($this->yuedu_model->get_book_sale_price($good_id)) / 100;  //普通价格
    
                    if($is_vip){
                        $price[] = doubleval($this->yuedu_model->get_vip_book_sale_price($good_id)) / 100;  //普通价格
                    }
    
                    if($is_vip) {
                        $vip_price = doubleval($this->activity_model->get_book_vip_price($good_id)) / 100;  //会员价格
                        if($original_price == round($vip_price, 2)) $params['active_price_flag'] = 1; //是否以活动价格购买
                        $price[] = $vip_price;
                    }
    
                    if(!in_array($original_price,$price) && -1 == $this->yuedu_model->deal_limit_book_sale_price($good_id,$original_price*100,$price)){
                        $this->response_error(41116, '活动时间已结束');
                    }
                    break;
                case 22://新版打包课程
                    $this->load->model('course_model');
                    $price[] = doubleval($this->course_model->get_course_package_price($body,$is_vip)) / 100;
                    break;
                case 23://新版打包小说
                    $this->load->model('yuedu_model');
                    $price[] = doubleval($this->yuedu_model->get_book_package_price($body,$is_vip)) / 100;
                    break;
                case 24://购物车购买(牛津书活动）
                    $cart_prices = [
                        "9000488"=>99/*牛津书活动*/, "9000648"=>399/*双语书打包活动*/, "9000646"=>148/*书课程组合活动*/, "21218147"=>298/*专攻背单词打包*/,"9000489"=>149,"9000490"=>399/*牛津书炒冷饭*/,
                        "1900001"=>199,"1900002"=>299, "1900003"=>399/*21周年超级礼包活动*/, '9000568' => 149/* 原版书打包活动 */, '9000569' => 349/* 原版书打包活动永久 */
                    ];
                    $price[] = $cart_prices[$good_id];
                    if($good_id == "9000648") {
                        $price[] = 199;
                    }
                    break;
                case 27: //四六级词汇
                    $this->load->model('write_model');
                    $price[] = doubleval($this->write_model->get_cet_word_price($good_id)) / 100;
                    break;
                case 30:
                case 31:
                    $this->load->model('chenxi_model');
                    $price = $this->chenxi_model->get_chenxi_price($good_id,$good_type);
                    $this->trade_verify_log->LogNotice('通过good_id= [ '.$good_id.' ] 查找数据库找到商品的原始价格、购买价格、邀请价格=='.$price[0]." ".$price[1]." ".$price[2]." ".$price[3]);
                    break;
                case 35:
                    $this->load->model('write_model');
                    $price[] = doubleval($this->write_model->get_similar_spe_price($good_id)) / 100;
                case 37:
                    $this->load->model('write_model');
                    $price[] = doubleval($this->write_model->get_spe_price($good_id)) / 100;
                    break;
                case 38:
                    $this->load->model('paper_model');
                    $price = doubleval($this->paper_model->get_paper_price($good_id)) / 100;
                    break;
                case 40:
                    $this->load->model('bonds_activity_model');
                    $price[] = doubleval($this->bonds_activity_model->get_bonus_activity_content_price($good_id)) / 100;
                    break;
                case 42:
                    if($original_price >= 30 && $original_price <= 50) {
                        $this->response_success($data);
                    } else {
                        $price[] = 50;
                    }
                    break;
                case 45:// 直播课
                    $price[] = 68;
                    break;
                default:
                    $this->response_error(31116, '商品类型错误');
                    break; 
            }
        }
        
        if(is_array($price)) { //放入价格数组
            foreach($price as $value) {
                $price_array[] = round($value, 2);
            }
        } else {
            $price_array[] = round($price, 2);
        }

        $this->trade_verify_log->LogNotice('通过good_id查找数据库找到商品的原始价格、购买价格、邀请价格=='.$price_array[0]." ".$price_array[1]." ".$price_array[2]." ".$price_array[3]);
        if(!in_array($original_price, $price_array)) {
            $this->trade_verify_log->LogNotice("user price illegal | uid=" . $uid . "|good_id=" . $good_id . "|body=" . $body . "|total_fee=" . $total_fee . "|original_price=" . $original_price . "|cheap_id=" . $cheap_id);
            $this->response_error(30006, '商品价格有误');
        }
        
        $this->response_success($data);
    }
    
    /**
     * 获取牛津离线词典价格
     * uid -- 用户id
     * date --- 购买时限 H = 半年 ， Y = 一年
     */
   function get_oxford_offline_price($uid, $date) {
        if($date == 'H') {
            $price_list =  array(PRICE_ORIGNAL_H, SALE_PRICE_H);
        } elseif($date == "Y") {
            $price_list =  array(PRICE_ORIGNAL_Y, SALE_PRICE_Y, SALE_PRICE_TOPUP_Y, 25);
        } elseif($date == "3Y") {
            $price_list =  array(PRICE_ORIGNAL_Y*3, SALE_PRICE_3Y);
        }
        
        return $price_list;
   }
   
   /**
     * 获取柯林斯离线词典价格
     * uid -- 用户id
     * date --- 购买时限  H = 半年 ， Y = 一年
     */
   function get_collins_offline_price($uid, $date) {
        if($date == 'H') {
            $price_list =  array(PRICE_COLLINS_ORIGNAL_H, COLLINS_SALE_PRICE_H);
        } elseif($date == "Y") {
            $price_list =  array(PRICE_COLLINS_ORIGNAL_Y, COLLINS_SALE_PRICE_Y, 25);
        } elseif($date == "3Y") {
            $price_list =  array(PRICE_COLLINS_ORIGNAL_Y*3, COLLINS_SALE_PRICE_3Y);
        }

        return $price_list;
   }
    /**
     * 获取牛津柯林斯离线词典价格
     * uid -- 用户id
     * date --- 购买时限  H = 半年 ， Y = 一年
     */
   function get_collins_oxford_offline_price($uid, $date) {
               if($date == 'Y') {
                   $price_list =  array(PRICE_COLLINS_ORIGNAL_Y + PRICE_ORIGNAL_Y, COLLINS_OXFORD_SALE_PRICE_Y);
               } elseif($date == "3Y") {
                   $price_list = array((PRICE_COLLINS_ORIGNAL_H + PRICE_ORIGNAL_H)*3,COLLINS_OXFORD_SALE_PRICE_3Y);
               }
               return $price_list;
   }
   
   /**
     * 获取获取人工销售价格
     */
    function get_manual_trans_price($uid, $book_id) {
        $request_data = array(
            'c' => 'translate',
            'm' => 'orderDetail',
            'client' => 6,
            'source' => 2,
            'v' => 1,
            'sv' => 1,
            'timestamp' => time(),
            'uuid' => '11111-11111-11111',
            'key' => '100006',
            'nonce_str' => md5(time()),
            'uid' => $uid,
            'ask_id' => $book_id,
        );
        
        $params = $request_data;
        ksort($params);
        reset($params);
        $str = '45asdfwerasdf1ewa';
        foreach ($params as $param_value) {
            $str = $str . trim($param_value);
        }
        $request_data['signature'] = md5($str);
        
        //构建请求
        $request_data = http_build_query($request_data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://translate.iciba.com/index.php?' . $request_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $_res = curl_exec($ch);
        curl_close($ch);
        
        //未返回任何东西
        if(empty($_res)) {
            header('Content-Type: application/json');
            die(json_encode(array('errno' => 30054, 'errmsg' => '人工翻译订单异常')));
        }
        
        $res = json_decode($_res, true);
        
        //订单返回异常
        if(!isset($res['errno'])) {
            header('Content-Type: application/json');
            die(json_encode(array('errno' => 30064, 'errmsg' => '人工翻译订单异常')));
        }
        
        //订单查询失败
        if($res['errno'] != 0) {
            header('Content-Type: application/json');
            die($_res);
        }
        
        return $res['base_price'] + $res['add_price'] + $res['ciba_award'];
    }
    
    /**
     * 获取VIP价格
     */
    function get_user_vip_price($uid, $dest_vip_type, $dest_vip_during_day, $client){
        // 判断是否是课程活动，是课程活动，课程会员的剩余天数<180天就可以购买
        $is_course_vip_flag = false;
        if ($dest_vip_type == 3) {
            $course_vip_info = $this->activity_model->get_vip_info($dest_vip_type, 365);
            $now_time = date('Y-m-d H:i:s');
            $activity_start_time = date('Y-m-d H:i:s', strtotime($course_vip_info["activity_start_time"]));
            $activity_end_time = date('Y-m-d H:i:s', strtotime($course_vip_info["activity_end_time"]));
            if (!empty($course_vip_info) && $activity_start_time < $now_time && $activity_end_time > $now_time) {
                $is_course_vip_flag = true;
            }
        }

        // client=9 表示课程会员活动
        if ($is_course_vip_flag) {
            $request_data = array(
                'c' => 'vip',
                'm' => 'get_vip_price',
                'client' => 1,// 课程会员活动ios也可以升级所以使用1
                'source' => 2,
                'v' => 1,
                'sv' => 1,
                'timestamp' => time(),
                'uuid' => '11111-11111-11111',
                'key' => '100006',
                'nonce_str' => md5(time()),
                'uid' => $uid,
                'dest_vip_type' => $dest_vip_type,
                'dest_vip_during_day' => $dest_vip_during_day,
                'surplus_day' => 180,
            );
        } else {
            $request_data = array(
                'c' => 'vip',
                'm' => 'get_vip_price',
                'client' => $_REQUEST['client'],
                'source' => 2,
                'v' => 1,
                'sv' => 1,
                'timestamp' => time(),
                'uuid' => '11111-11111-11111',
                'key' => '100006',
                'nonce_str' => md5(time()),
                'uid' => $uid,
                'dest_vip_type' => $dest_vip_type,
                'dest_vip_during_day' => $dest_vip_during_day,
            );
        }

        //构建请求
        $request_data = http_build_query($request_data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://my.iciba.com/vip/index.php?' . $request_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $_res = curl_exec($ch);
        curl_close($ch);

        //未返回任何东西
        if(empty($_res)) {
            header('Content-Type: application/json');
            die(json_encode(array('errno' => 30754, 'errmsg' => '价格获取异常')));
        }

        $res = json_decode($_res, true);

        //订单返回异常
        if(!isset($res['errno'])) {
            header('Content-Type: application/json');
            die(json_encode(array('errno' => 30064, 'errmsg' => '价格获取异常')));
        }
        
        //订单查询失败
        if($res['errno'] != 0) {
            header('Content-Type: application/json');
            die($_res);
        }
        
        //状态
        if($res['click_flag'] == 'have' || $res['click_flag'] == 'none') {
            header('Content-Type: application/json');
            die(json_encode(array('errno' => 30364, 'errmsg' => '已经购买')));
        }
        
        $price_list[] = $res['price'];
        $price_list[] = $res['ios_price'];
        $price_list[] = $res['activity_price'];
        $price_list[] = $res['activity_ios_price'];
        $price_list[] = $res['price'];
        if(3 == $dest_vip_type){
            $price_list[] = 359;
        }
        
        
        $result = array();
        
        //扣除最近买书价格, 买书之后价格
        $this->load->model('trade_model');
        $latest_buy_book_price = $this->trade_model->get_user_latest_book_pay_price($uid);
        
        foreach($price_list as $price) {
            $result[] = $price;
            if($dest_vip_type == 2 && $latest_buy_book_price != 0) {
                $result[] = $price - $latest_buy_book_price;
            }
        }
        
        return $result;
    }
    
    /**
     * 查看书籍是否已经购买
     * @param $data 更新数据
     * return  true - 是 false - 否 
     */
    function isBookBuy($uid, $bookId) {
        $key = 1000005;
        $timestamp = time();
        $type = 1;
        $secret = "5dabcfd3f5f4c8422a680379438cec7b";
        $signature = md5($key . $timestamp . $secret . $uid . $bookId . $type);
        $url = "http://service.iciba.com/yuedu/book/isbuy?uid=".$uid."&bookId=".$bookId."&key=".$key."&timestamp=".$timestamp."&type=".$type."&signature=".$signature;
        
        $i = 0;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT , 3);
        $send_res = curl_exec($ch);
        curl_close($ch);
        
        if($send_res == 1){  //失败则打普通警告日志
            return true;
        } else {
            return false;
        }
    }
}

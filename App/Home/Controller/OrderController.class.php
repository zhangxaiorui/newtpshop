<?php
/**
 * Created by PhpStorm.
 * OrderController.class.php
 * author: Terry
 * Date: 2017-10-9
 * Time: 23:24
 * description:
 */

namespace Home\Controller;
use Boris\DumpInspector;

class OrderController extends CommonController{
    /**
     * @check 订单结算
     * @author : Terry
     * @return
     */
    public function check(){

        $this->checkLogin(U('Order/check'));
        $cart = D('Cart');
        $data = $cart->getListData();
//        dump($data);
        $this->assign('data',$data);
        $goodsPrice = $cart->getPrice($data);
        $this->assign('goodsPrice',$goodsPrice);
//        dump($goodsPrice);
        $this->display();

    }



    /**
     * @order 订单
     * @author : Terry
     * @return
     */
    public  function  order(){
        $order=D('Order');
        $res = $order->postOrder();
        if (!$res){
            $this->error($order->getError());
        }
        if ($res){
            $order_id =$res['id'] ;
            $order_name = 'jx_'.$res['id'].uniqid();
            $order_price = $res['total_price'];
            $order_body ='这只是一个测试' ;
            postAlipay($order_id,$order_name,$order_price,$order_body);
        }
    }
    /**
     * returnUrl 支付宝回调
     *
     * author :Terry
     * return :
     */
    public function  returnUrl(){
    Vendor('Alipay.pagepay.service.AlipayTradeService');
    $alipayReturn=$_GET;
    $alipaySevice = new \AlipayTradeService(C('ALIPAY'));
    $result = $alipaySevice->check($alipayReturn);
//dump($arr);exit;
    if($result) {//验证成功
        //商户订单号
        $out_trade_no = htmlspecialchars($_GET['out_trade_no']);
        //支付宝交易号
        $trade_no = htmlspecialchars($_GET['trade_no']);
        $data =  D('order')->where(['id'=>$out_trade_no])->setField(['pay_status'=>1,'alipay_num'=>$trade_no]);
           if ($data){
               D('User')->addJiFen(intval($alipayReturn['total_amount']));
               $this->success('付款成功',U('Member/myOrder'));
           }
    }
    else {
        //验证失败
        echo "验证失败";
    }

    }

    /**
     * @goOnPay 继续支付
     * @author : Terry
     * @return
     */
    public function goOnPay(){
        $order_id =  intval(I('get.order_id'));
        $order = D('order');
        $data = $order->where(['id'=>$order_id])->find();
        if (!$data){
            $this->error('参数错误');
        }
        if ($data['pay_status']==1){
            $this->error('该订单已付款');
        }
        $order_name = 'jx_'.$data['id'].uniqid();
        
        postAlipay($data['id'],$order_name,$data['total_price']);

    }
}
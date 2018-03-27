<?php
return [
    'show_error_msg'           => true,
    'cmf_default_theme'        => 'datong_car',
    'insurance_order_status'   =>
    [
        -11 => '过期失效',
        -3  => '管理员取消',
        -2  => '卖家取消',
        -1  => '取消/关闭',
        0   => '待审核',
        1   => '已审核',
        5   => '已确认合同',
        6   => '已支付',
        10  => '完成',
    ],
    'service_status'           =>
    [
        -11 => '过期',
        -5  => '卖家取消失败',
        -4  => '买家取消失败',
        -3  => '管理员取消',
        -2  => '卖家取消',
        -1  => '买家取消',
        0   => '预约中……',
        1   => '预约成功',
        2   => '取车中……',
        3   => '正在检测……',
        4   => '检测完成',
        9   => '送回中……',
        10  => '完成',
    ],
    'service_pay_status'    =>
    [
        -10 => '不需要支付',
        0   => '未支付',
        1   => '支付核实',
        10  => '支付完成',
    ],
    'service_define_data'      =>
    [
        'username'        => '车主名',
        'contact'         => '联系方式',
        'telephone'       => '电话',
        'birthday'        => '生日',
        'address'         => '详情地址',
        'seller_name'     => '卖家名',
        'seller_contact'  => '卖家联系方式',
        'seller_birthday' => '卖家生日',
        'plateNo'         => '车牌号码',
        'car_vin'         => '车架号',
        'reg_time'        => '注册日期',
        'identity_card'   => '身份证',
        'driving_license' => '行驶证',
        'qualified'       => '合格证',
        'loan_invoice'    => '贷款发票',
        'appoint_time'    => '预约时间',
        'service_point'   => '服务点',
        'tire_size'       => '轮胎尺寸',
        'car_mileage'     => '公里数',
    ],
    'service_define_type'   =>
    [
        'text'     => '文本类型',
        'number'   => '数字型',
        'select'   => '选择框',
        'textarea' => '文本框',
        'file'     => '附件',
        'image'    => '单图',
        'photos'   => '多图',
        'date'     => '日期时间',
    ],
    'trade_order_status'       =>
    [
        -11 => '过期',
        -5  => '卖家取消失败',
        -4  => '买家取消失败',
        -3  => '管理员取消',
        -2  => '卖家取消',
        -1  => '买家取消',
        0   => '未支付订金',
        1   => '预约中',
        8   => '全部支付',
        10  => '完成',
    ],
    'trade_report_status'      =>
    [
        '1' => '正常',
        '2' => '未检测',
        '3' => '轻微受损',
        '4' => '严重受损',
    ],
    'usual_car_type'           =>
    [
        1 => '准新车',
        2 => '练手车',
        3 => '分期购',
    ],
    'usual_car_filter_var01'   => 'car_displacement,car_seating',
    'usual_car_filter_var02'   => 'car_mileage,car_displacement,car_seating',
    'usual_car_filter_var'     => 'car_gearbox,car_effluent,car_fuel,car_color',
    'usual_car_filter_var2'    => 'car_structure',
    'usual_car_sell_status'    =>
    [
        -11 => '售罄',
        -2  => '禁止出售',
        -1  => '下架',
        0   => '初始态',
        1   => '上架(售卖中)',
        2   => '已下单',
        3   => '已付款',
        10  => '完成',
    ],
    'usual_car_status'         =>
    [
        -1 => '禁用',
        0  => '隐藏',
        1  => '显示',
    ],
    'usual_item_cate_codetype' =>
    [
        'text'     => '文本类型',
        'select'   => '下拉框',
        'radio'    => '单选框',
        'checkbox' => '复选框',
        'number'   => '数字型',
        'hidden'   => '隐藏域',
    ],
    'verify_status'            =>
    [
        -3 => '认证失败',
        -2 => '取消',
        -1 => '禁止认证',
        0  => '未认证',
        1  => '已认证',
        2  => '重新认证',
    ],
    'verify_define_data'       =>
    [
        'mobile'            => '手机号',
        'email'             => '邮箱',
        'driving_license'   => '行驶证',
        'identity_card'     => '身份证',
        'plateNo'           => '车牌号',
        'username'          => '用户名',
        'real_name'         => '真名',
        'contact'           => '联系方式',
        'telephone'         => '电话',
        'gender'            => '性别',
        'birthday'          => '生日',
        'alipay'            => '支付宝',
        'weixin'            => '微信',
        'ID_Type'           => '证件类型',
        'ID_No'             => '证件号码',
        'booklet'           => '户口本',
        'house_certificate' => '房产证',
        'marriage_lines'    => '结婚证',
        'birthcity'         => '出生地',
        'residecity'        => '居住地',
        'diploma'           => '毕业证书',
        'graduateschool'    => '毕业院校',
        'education'         => '学历',
        'business_license'  => '营业执照',
        'work_occupation'   => '职业',
        'work_company'      => '公司',
        'work_position'     => '职位',
        'work_experience'   => '工作经历',
    ],
    'user_funds_log_type'      =>
    [
        -9 => '提现失败退回',
        -7 => '车商城付余款退回',
        -6 => '车商城订金退回',
        -5 => '申请开店押金退回',
        -3 => '服务预约金退回',
        -1 => '保险预约金退回',
        0  => '未知',
        1  => '保险预约金',
        2  => '保险金',
        3  => '服务预约金',
        4  => '服务费',
        5  => '申请开店押金',
        6  => '车商城订金',
        7  => '车商城付余款',
        8  => '充值',
        9  => '提现',
        10 => '转账',
        11 => '管理员操作',
    ],
    'funds_apply_type'         =>
    [
        'withdraw' => '提现',
        'openshop' => '开店押金',
        'recharge' => '充值',
        'admin'    => '管理员操作',
    ],
    'funds_apply_status'       =>
    [
        -2 => '已取消',
        -1 => '审核失败',
        0  => '审核中……',
        1  => '审核通过',
        10 => '完成',
    ],
    'payment'                  =>
    [
        'null'        => 'unknow',
        'admin'       => '管理员操作',
        'cash'        => '余额',
        'alipay'      => '支付宝',
        'wxpay'       => '微信',
        'alipayQR'    => '支付宝扫码支付',
        'alipaywap'   => '支付宝手机支付',
        'wxpayjs'     => '微信在线支付',
        'wxpaynative' => '微信扫码支付',
        'bankpay'     => '网银在线',
    ],
    'news_adminurl'            =>
    [
        1 => 'trade/AdminOrder/index',
        2 => 'insurance/AdminOrder/index',
        3 => 'service/AdminService/index',
        4 => 'user/AdminIndex/index',
        5 => 'funds/AdminWithdraw/index',
        6 => 'funds/AdminRecharge/addTicket',
        7 => 'usual/AdminVerify/index',
        8 => 'funds/AdminOpenshop/index',
        9 => 'funds/AdminRecharge/add',
        10 => 'shop/AdminOrder/index',
    ],
    'shop_goods_status'        =>
    [
        -1  => '下架',
        1   => '上架(售卖中)',
    ],
    'shop_order_status'        =>
    [
        -11  => '过期',
        -2  => '卖家取消',
        -1  => '买家取消',
        0   => '待付款',
        1   => '待审查',
        2   => '待发货',
        3   => '待收货',
        4   => '待评价',
    ],
    'shop_refund_status' => [
        0 => '未申请',
        1 => '退款中',
        2 => '退款完成',
    ],
    'thumbnail_size'=>[[565,385],[283,195],[160,109]],
    'pagerset'=>['size' => 12,],
];
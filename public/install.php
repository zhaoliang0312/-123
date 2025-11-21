<?php
// 安装程序文件
session_start();

// 检测是否已安装
if (file_exists(__DIR__ . '/../installed.lock')) {
    exit('程序已安装，如需重新安装，请删除根目录下的 installed.lock 文件。');
}

// 定义项目根目录
define('ROOT_PATH', dirname(__DIR__));

// 错误处理函数
function show_error($msg) {
    exit("<div style='color:red;font-weight:bold;font-size:14px;'>{$msg}</div><a href='javascript:history.back();'>返回</a>");
}

// 成功信息处理函数
function show_success($msg, $url = '') {
    if ($url) {
        exit("<div style='color:green;font-weight:bold;font-size:14px;'>{$msg}</div><br/><a href='{$url}'>跳转</a>");
    } else {
        exit("<div style='color:green;font-weight:bold;font-size:14px;'>{$msg}</div>");
    }
}

// 检查环境
function check_env() {
    $items = [
        'php_version' => ['是否满足 PHP 7.2+', PHP_VERSION >= '7.2', 'PHP版本不满足要求，需要 PHP 7.2+'],
        'pdo' => ['是否支持PDO', extension_loaded('pdo'), '请安装PDO扩展'],
        'pdo_mysql' => ['是否支持PDO MySQL', extension_loaded('pdo_mysql'), '请安装PDO MySQL扩展'],
        'mbstring' => ['是否支持mbstring', extension_loaded('mbstring'), '请安装mbstring扩展'],
        'curl' => ['是否支持curl', extension_loaded('curl'), '请安装curl扩展'],
        'openssl' => ['是否支持openssl', extension_loaded('openssl'), '请安装openssl扩展'],
        'write_permission' => ['目录写入权限', is_writable(ROOT_PATH.'/runtime') && is_writable(ROOT_PATH.'/.env'), '请设置runtime目录和.env文件为可写']
    ];
    
    $success = true;
    $html = '<table border="1" cellpadding="10" cellspacing="0" style="border-collapse:collapse;margin:20px 0;">';
    $html .= '<tr><th>检测项</th><th>结果</th><th>说明</th></tr>';
    
    foreach ($items as $item) {
        $html .= '<tr>';
        $html .= '<td>' . $item[0] . '</td>';
        if ($item[1]) {
            $html .= '<td style="color:green;">通过</td><td>-</td>';
        } else {
            $html .= '<td style="color:red;">未通过</td><td>' . $item[2] . '</td>';
            $success = false;
        }
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    return ['success' => $success, 'html' => $html];
}

// 生成随机字符串
function random_str($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

// 执行SQL
function execute_sql($host, $port, $username, $password, $database, $charset = 'utf8') {
    try {
        // 创建数据库连接
        $dsn = "mysql:host={$host};port={$port}";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查数据库是否存在，不存在则创建
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET {$charset}");
        $pdo->exec("USE `{$database}`");
        
        // 读取SQL文件
        $sql_file = ROOT_PATH . '/qsy.sql';
        if (!file_exists($sql_file)) {
            return ['success' => false, 'message' => 'SQL文件不存在'];
        }
        
        $sql_content = file_get_contents($sql_file);
        if (!$sql_content) {
            return ['success' => false, 'message' => '无法读取SQL文件内容'];
        }
        
        // 执行SQL语句
        $pdo->exec($sql_content);
        
        return ['success' => true, 'message' => '数据库导入成功'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '数据库错误: ' . $e->getMessage()];
    }
}

// 写入配置文件
function write_config($config) {
    $env_config = <<<EOT
APP_DEBUG = false

[APP]
DEFAULT_TIMEZONE = Asia/Shanghai

[DATABASE]
TYPE = mysql
HOSTNAME = {$config['db_host']}
DATABASE = {$config['db_name']}
USERNAME = {$config['db_user']}
PASSWORD = {$config['db_password']}
HOSTPORT = {$config['db_port']}
CHARSET = utf8
DEBUG = false

[LANG]
default_lang = zh-cn
EOT;

    // 写入.env文件
    if (file_put_contents(ROOT_PATH . '/.env', $env_config) === false) {
        return ['success' => false, 'message' => '无法写入.env配置文件'];
    }
    
    return ['success' => true, 'message' => '配置文件写入成功'];
}

// 修改API密钥和其他配置
function update_api_config($config, $db_config) {
    try {
        // 连接数据库
        $dsn = "mysql:host={$db_config['db_host']};port={$db_config['db_port']};dbname={$db_config['db_name']}";
        $pdo = new PDO($dsn, $db_config['db_user'], $db_config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 修改API文件中的密钥和域名
        $api_file = ROOT_PATH . '/app/controller/Api.php';
        if (!file_exists($api_file)) {
            return ['success' => false, 'message' => 'API文件不存在'];
        }
        
        $api_content = file_get_contents($api_file);
        
        // 替换微信密钥
        $api_content = preg_replace(
            '/\$secret\s*=\s*"[^"]*";/', 
            '$secret = "' . $config['wx_secret'] . '";', 
            $api_content
        );
        
        // 替换后台登录密码
        $api_content = preg_replace(
            '/if\s*\(\s*\$key\s*==\s*"[^"]*"\s*\)/', 
            'if ($key == "' . $config['admin_key'] . '")', 
            $api_content
        );
        
        // 替换下载域名
        $api_content = preg_replace(
            '/\$this->returnJson\(0,\s*"[^"]*",\s*"操作成功！"\);/', 
            '$this->returnJson(0, "' . $config['domain'] . '/down.php?url=", "操作成功！");', 
            $api_content
        );
        
        // 写入修改后的文件
        if (file_put_contents($api_file, $api_content) === false) {
            return ['success' => false, 'message' => '无法写入API配置文件'];
        }
        
        return ['success' => true, 'message' => 'API配置修改成功'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '数据库错误: ' . $e->getMessage()];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '错误: ' . $e->getMessage()];
    }
}

// 创建安装锁定文件
function create_lock_file() {
    $lock_file = ROOT_PATH . '/installed.lock';
    $content = date('Y-m-d H:i:s');
    
    if (file_put_contents($lock_file, $content) === false) {
        return ['success' => false, 'message' => '无法创建安装锁定文件'];
    }
    
    return ['success' => true, 'message' => '安装锁定文件创建成功'];
}

// 处理安装请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step'])) {
    $step = $_POST['step'];
    
    // 第一步：检测环境
    if ($step == 'check_env') {
        $check = check_env();
        echo json_encode($check);
        exit;
    }
    
    // 第二步：数据库配置
    if ($step == 'setup_db') {
        $db_host = isset($_POST['db_host']) ? trim($_POST['db_host']) : '';
        $db_port = isset($_POST['db_port']) ? trim($_POST['db_port']) : '3306';
        $db_user = isset($_POST['db_user']) ? trim($_POST['db_user']) : '';
        $db_password = isset($_POST['db_password']) ? trim($_POST['db_password']) : '';
        $db_name = isset($_POST['db_name']) ? trim($_POST['db_name']) : '';
        
        if (empty($db_host) || empty($db_user) || empty($db_name)) {
            echo json_encode(['success' => false, 'message' => '请填写完整的数据库信息']);
            exit;
        }
        
        // 保存数据库配置
        $_SESSION['db_config'] = [
            'db_host' => $db_host,
            'db_port' => $db_port,
            'db_user' => $db_user,
            'db_password' => $db_password,
            'db_name' => $db_name
        ];
        
        // 测试数据库连接
        try {
            $dsn = "mysql:host={$db_host};port={$db_port}";
            $pdo = new PDO($dsn, $db_user, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo json_encode(['success' => true, 'message' => '数据库连接成功']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // 第三步：网站设置
    if ($step == 'setup_site') {
        $domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';
        $wx_secret = isset($_POST['wx_secret']) ? trim($_POST['wx_secret']) : '';
        $admin_key = isset($_POST['admin_key']) ? trim($_POST['admin_key']) : '';
        
        if (empty($domain) || empty($wx_secret) || empty($admin_key)) {
            echo json_encode(['success' => false, 'message' => '请填写完整的网站设置信息']);
            exit;
        }
        
        // 保存网站设置
        $_SESSION['site_config'] = [
            'domain' => $domain,
            'wx_secret' => $wx_secret,
            'admin_key' => $admin_key
        ];
        
        echo json_encode(['success' => true, 'message' => '网站设置已保存']);
        exit;
    }
    
    // 第四步：开始安装
    if ($step == 'install') {
        if (!isset($_SESSION['db_config']) || !isset($_SESSION['site_config'])) {
            echo json_encode(['success' => false, 'message' => '请先完成数据库配置和网站设置']);
            exit;
        }
        
        $db_config = $_SESSION['db_config'];
        $site_config = $_SESSION['site_config'];
        
        // 1. 导入数据库
        $sql_result = execute_sql(
            $db_config['db_host'],
            $db_config['db_port'],
            $db_config['db_user'],
            $db_config['db_password'],
            $db_config['db_name']
        );
        
        if (!$sql_result['success']) {
            echo json_encode($sql_result);
            exit;
        }
        
        // 2. 写入配置
        $config_result = write_config($db_config);
        if (!$config_result['success']) {
            echo json_encode($config_result);
            exit;
        }
        
        // 3. 更新API配置
        $api_result = update_api_config($site_config, $db_config);
        if (!$api_result['success']) {
            echo json_encode($api_result);
            exit;
        }
        
        // 4. 创建安装锁定文件
        $lock_result = create_lock_file();
        if (!$lock_result['success']) {
            echo json_encode($lock_result);
            exit;
        }
        
        // 清除session
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => '安装成功']);
        exit;
    }
}

// 显示安装页面
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThinkPHP 去水印工具安装程序</title>
    <style>
        :root {
            --primary-color: #4285f4;
            --success-color: #34a853;
            --warning-color: #fbbc05;
            --danger-color: #ea4335;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: "Microsoft YaHei", "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 850px;
            margin: 30px auto;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .header {
            background: var(--primary-color);
            color: white;
            padding: 25px 30px;
            position: relative;
        }
        .header h1 {
            font-weight: 500;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            margin-top: 8px;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        h2 {
            color: var(--dark-color);
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .step {
            display: none;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.2);
            outline: none;
        }
        small {
            display: block;
            margin-top: 5px;
            color: #777;
            font-size: 12px;
        }
        .button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            cursor: pointer;
            font-size: 15px;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .button:hover {
            background: #3367d6;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .button.secondary {
            background: #f1f3f4;
            color: #333;
        }
        .button.secondary:hover {
            background: #e2e6ea;
        }
        .button.success {
            background: var(--success-color);
        }
        .button.success:hover {
            background: #2d9748;
        }
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .error {
            color: var(--danger-color);
            margin-top: 10px;
            padding: 10px;
            background-color: rgba(234, 67, 53, 0.1);
            border-radius: 4px;
            font-size: 14px;
        }
        .success {
            color: var(--success-color);
            margin-top: 10px;
            padding: 10px;
            background-color: rgba(52, 168, 83, 0.1);
            border-radius: 4px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 0 0 1px #eee;
        }
        th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
        }
        td {
            padding: 12px 15px;
            border-top: 1px solid #eee;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .progress-container {
            margin: 25px 0;
            height: 8px;
            background-color: #e9ecef;
            border-radius: 100px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            width: 0;
            background-color: var(--success-color);
            transition: width 0.6s ease;
            border-radius: 100px;
        }
        .progress-text {
            text-align: center;
            margin-top: 8px;
            font-size: 14px;
            color: #666;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 20px;
            position: relative;
            font-size: 14px;
            font-weight: 500;
            z-index: 1;
        }
        .step-dot.active {
            background-color: var(--primary-color);
            color: white;
        }
        .step-dot.completed {
            background-color: var(--success-color);
            color: white;
        }
        .step-connector {
            position: absolute;
            height: 2px;
            background-color: #e9ecef;
            width: 40px;
            top: 50%;
            transform: translateY(-50%);
        }
        .step-connector.left {
            left: -30px;
        }
        .step-connector.right {
            right: -30px;
        }
        .step-dot.active .step-connector.left,
        .step-dot.completed .step-connector {
            background-color: var(--success-color);
        }
        .install-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .install-summary ol {
            margin-left: 20px;
            margin-top: 10px;
        }
        .install-summary li {
            margin-bottom: 8px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.success {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--success-color);
        }
        .badge.error {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--danger-color);
        }
        @media (max-width: 767px) {
            .container {
                margin: 10px auto;
            }
            .header, .content {
                padding: 20px;
            }
            .buttons {
                flex-direction: column;
                gap: 10px;
            }
            .button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>去水印工具 安装向导</h1>
            <p>一键安装配置您的去水印工具系统</p>
        </div>
        
        <div class="content">
            <div class="step-indicator">
                <div class="step-dot active" id="dot-1">
                    1
                    <div class="step-connector right"></div>
                </div>
                <div class="step-dot" id="dot-2">
                    2
                    <div class="step-connector left"></div>
                    <div class="step-connector right"></div>
                </div>
                <div class="step-dot" id="dot-3">
                    3
                    <div class="step-connector left"></div>
                    <div class="step-connector right"></div>
                </div>
                <div class="step-dot" id="dot-4">
                    4
                    <div class="step-connector left"></div>
                </div>
            </div>
            
            <div id="step-1" class="step active">
                <h2>环境检测</h2>
                <p>系统将检查您的服务器环境是否满足运行要求。</p>
                <div id="env-check-results"></div>
                <div class="buttons">
                    <div></div>
                    <div>
                        <button class="button" onclick="checkEnvironment()">检测环境</button>
                        <button class="button success" id="next-step-1" style="display:none;" onclick="nextStep(1)">下一步</button>
                    </div>
                </div>
            </div>
            
            <div id="step-2" class="step">
                <h2>数据库配置</h2>
                <p>请填写您的MySQL数据库连接信息。</p>
                <div class="form-group">
                    <label>数据库主机</label>
                    <input type="text" id="db_host" value="127.0.0.1" placeholder="例如: localhost或127.0.0.1">
                </div>
                <div class="form-group">
                    <label>数据库端口</label>
                    <input type="text" id="db_port" value="3306" placeholder="例如: 3306">
                </div>
                <div class="form-group">
                    <label>数据库用户名</label>
                    <input type="text" id="db_user" placeholder="例如: root">
                </div>
                <div class="form-group">
                    <label>数据库密码</label>
                    <input type="password" id="db_password" placeholder="数据库密码">
                </div>
                <div class="form-group">
                    <label>数据库名称</label>
                    <input type="text" id="db_name" placeholder="例如: qsy">
                    <small>如不存在将自动创建数据库</small>
                </div>
                <div id="db-message"></div>
                <div class="buttons">
                    <button class="button secondary" onclick="prevStep(2)">上一步</button>
                    <div>
                        <button class="button" onclick="setupDatabase()">测试连接</button>
                        <button class="button success" id="next-step-2" style="display:none;" onclick="nextStep(2)">下一步</button>
                    </div>
                </div>
            </div>
            
            <div id="step-3" class="step">
                <h2>网站设置</h2>
                <p>配置您的网站基本信息。</p>
                <div class="form-group">
                    <label>网站域名</label>
                    <input type="text" id="domain" value="https://c.776k.cn" placeholder="例如: https://c.776k.cn">
                    <small>请输入带协议头的完整域名，如 https://c.776k.cn</small>
                </div>
                <div class="form-group">
                    <label>微信小程序密钥</label>
                    <input type="text" id="wx_secret" value="8c4a5c0630ca4502a8b61b1e6cdf205a" placeholder="例如: 8c4a5c0630ca4502a8b61b1e6cdf205a">
                    <small>在微信小程序后台获取: mp.weixin.qq.com</small>
                </div>
                <div class="form-group">
                    <label>后台登录密钥</label>
                    <input type="text" id="admin_key" value="test" placeholder="例如: test">
                    <small>用于管理员登录的密钥，请修改默认值</small>
                </div>
                <div id="site-message"></div>
                <div class="buttons">
                    <button class="button secondary" onclick="prevStep(3)">上一步</button>
                    <div>
                        <button class="button" onclick="setupSite()">保存设置</button>
                        <button class="button success" id="next-step-3" style="display:none;" onclick="nextStep(3)">下一步</button>
                    </div>
                </div>
            </div>
            
            <div id="step-4" class="step">
                <h2>开始安装</h2>
                <div class="install-summary">
                    <p>程序将执行以下操作:</p>
                    <ol>
                        <li>导入数据库结构和初始数据</li>
                        <li>创建配置文件</li>
                        <li>更新API配置信息</li>
                        <li>创建安装锁定文件</li>
                    </ol>
                </div>
                <div class="progress-container">
                    <div id="progress-bar" class="progress-bar"></div>
                </div>
                <div class="progress-text" id="progress-text">0%</div>
                <div id="install-message"></div>
                <div class="buttons">
                    <button class="button secondary" onclick="prevStep(4)">上一步</button>
                    <div>
                        <button class="button success" id="install-button" onclick="startInstall()">开始安装</button>
                        <a class="button success" id="finish-button" href="/" style="display:none;">完成安装</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 当前步骤
        let currentStep = 1;
        
        // 更新步骤指示器
        function updateStepIndicator(step) {
            // 清除所有样式
            for (let i = 1; i <= 4; i++) {
                document.getElementById(`dot-${i}`).className = 'step-dot';
            }
            
            // 设置当前步骤为active，之前步骤为completed
            for (let i = 1; i < step; i++) {
                document.getElementById(`dot-${i}`).className = 'step-dot completed';
            }
            document.getElementById(`dot-${step}`).className = 'step-dot active';
        }
        
        // 切换到下一步
        function nextStep(step) {
            document.getElementById(`step-${step}`).classList.remove('active');
            document.getElementById(`step-${step+1}`).classList.add('active');
            currentStep = step + 1;
            updateStepIndicator(currentStep);
            window.scrollTo(0, 0);
        }
        
        // 切换到上一步
        function prevStep(step) {
            document.getElementById(`step-${step}`).classList.remove('active');
            document.getElementById(`step-${step-1}`).classList.add('active');
            currentStep = step - 1;
            updateStepIndicator(currentStep);
            window.scrollTo(0, 0);
        }
        
        // 检测环境
        function checkEnvironment() {
            const loadingHtml = '<div style="text-align:center;padding:20px;"><span style="display:inline-block;width:20px;height:20px;border:2px solid #4285f4;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;"></span> <span style="margin-left:10px;">正在检测环境...</span></div>';
            document.getElementById('env-check-results').innerHTML = loadingHtml;
            
            fetch('install.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'step=check_env'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('env-check-results').innerHTML = data.html;
                if (data.success) {
                    document.getElementById('next-step-1').style.display = 'inline-block';
                } else {
                    document.getElementById('next-step-1').style.display = 'none';
                }
            })
            .catch(error => {
                document.getElementById('env-check-results').innerHTML = '<div class="error">检测环境时发生错误: ' + error + '</div>';
            });
        }
        
        // 设置数据库
        function setupDatabase() {
            const db_host = document.getElementById('db_host').value;
            const db_port = document.getElementById('db_port').value;
            const db_user = document.getElementById('db_user').value;
            const db_password = document.getElementById('db_password').value;
            const db_name = document.getElementById('db_name').value;
            
            if (!db_host || !db_user || !db_name) {
                document.getElementById('db-message').innerHTML = '<div class="error">请填写完整的数据库信息</div>';
                return;
            }
            
            document.getElementById('db-message').innerHTML = '<div style="text-align:center;padding:10px;"><span style="display:inline-block;width:16px;height:16px;border:2px solid #4285f4;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;"></span> <span style="margin-left:10px;">正在测试连接...</span></div>';
            
            fetch('install.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `step=setup_db&db_host=${encodeURIComponent(db_host)}&db_port=${encodeURIComponent(db_port)}&db_user=${encodeURIComponent(db_user)}&db_password=${encodeURIComponent(db_password)}&db_name=${encodeURIComponent(db_name)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('db-message').innerHTML = '<div class="success">' + data.message + '</div>';
                    document.getElementById('next-step-2').style.display = 'inline-block';
                } else {
                    document.getElementById('db-message').innerHTML = '<div class="error">' + data.message + '</div>';
                    document.getElementById('next-step-2').style.display = 'none';
                }
            })
            .catch(error => {
                document.getElementById('db-message').innerHTML = '<div class="error">设置数据库时发生错误: ' + error + '</div>';
            });
        }
        
        // 设置网站
        function setupSite() {
            const domain = document.getElementById('domain').value;
            const wx_secret = document.getElementById('wx_secret').value;
            const admin_key = document.getElementById('admin_key').value;
            
            if (!domain || !wx_secret || !admin_key) {
                document.getElementById('site-message').innerHTML = '<div class="error">请填写完整的网站设置信息</div>';
                return;
            }
            
            document.getElementById('site-message').innerHTML = '<div style="text-align:center;padding:10px;"><span style="display:inline-block;width:16px;height:16px;border:2px solid #4285f4;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;"></span> <span style="margin-left:10px;">正在保存设置...</span></div>';
            
            fetch('install.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `step=setup_site&domain=${encodeURIComponent(domain)}&wx_secret=${encodeURIComponent(wx_secret)}&admin_key=${encodeURIComponent(admin_key)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('site-message').innerHTML = '<div class="success">' + data.message + '</div>';
                    document.getElementById('next-step-3').style.display = 'inline-block';
                } else {
                    document.getElementById('site-message').innerHTML = '<div class="error">' + data.message + '</div>';
                    document.getElementById('next-step-3').style.display = 'none';
                }
            })
            .catch(error => {
                document.getElementById('site-message').innerHTML = '<div class="error">设置网站时发生错误: ' + error + '</div>';
            });
        }
        
        // 开始安装
        function startInstall() {
            document.getElementById('install-button').disabled = true;
            document.getElementById('install-message').innerHTML = '<div class="badge success">正在准备安装...</div>';
            
            // 更新进度条
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
            
            setTimeout(() => {
                progressBar.style.width = '30%';
                progressText.textContent = '30% - 正在导入数据...';
                
                fetch('install.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'step=install'
                })
                .then(response => response.json())
                .then(data => {
                    setTimeout(() => {
                        progressBar.style.width = '100%';
                        progressText.textContent = '100% - 安装完成';
                        
                        if (data.success) {
                            document.getElementById('install-message').innerHTML = `
                                <div class="success">
                                    <div style="margin-bottom:10px;"><strong>🎉 恭喜！安装已成功完成</strong></div>
                                    <p>${data.message}</p>
                                    <p style="margin-top:10px;">您现在可以访问您的去水印工具网站了。</p>
                                </div>`;
                            document.getElementById('install-button').style.display = 'none';
                            document.getElementById('finish-button').style.display = 'inline-block';
                        } else {
                            document.getElementById('install-message').innerHTML = '<div class="error"><strong>❌ 安装失败</strong><br>' + data.message + '</div>';
                            document.getElementById('install-button').disabled = false;
                        }
                    }, 500);
                })
                .catch(error => {
                    progressBar.style.width = '100%';
                    progressText.textContent = '安装出错';
                    document.getElementById('install-message').innerHTML = '<div class="error"><strong>❌ 安装出错</strong><br>安装时发生错误: ' + error + '</div>';
                    document.getElementById('install-button').disabled = false;
                });
            }, 500);
        }
        
        // 添加旋转动画
        const styleSheet = document.createElement("style");
        styleSheet.textContent = `
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(styleSheet);
        
        // 页面加载后自动检测环境
        window.onload = function() {
            checkEnvironment();
        };
    </script>
</body>
</html> 
<?php

/**
 * 添加评论
 */
function addComment($self)
{
    $self->response->setStatus(200);
    $options = Helper::options();
    $user = Typecho_Widget::widget('Widget_User');
    $db = Typecho_Db::get();

    // // Security 验证不通过时会直接跳转，所以需要自己进行判断
    // // 需要开启反垃圾保护，此时将不验证来源
    // if ($self->request->get('_') != Helper::security()->getToken($self->request->getReferer())) {
    //     $self->response->throwJson(array('status' => 0, 'msg' => _t('非法请求')));
    // }

    /** 检查ip评论间隔 */
    if (!$user->pass('editor', true) && $self->authorId != $user->uid && $options->commentsPostIntervalEnable) {
        $latestComment = $db->fetchRow($db->select('created')->from('table.comments')
            ->where('cid = ?', $self->request->cid)
            ->where('ip = ?', $self->request->getIp())
            ->order('created', Typecho_Db::SORT_DESC)
            ->limit(1));

        if ($latestComment && ($options->gmtTime - $latestComment['created'] > 0 && $options->gmtTime - $latestComment['created'] < $options->commentsPostInterval)) {
            $self->response->throwJson(array('status' => 0, 'msg' => _t('对不起, 您的发言过于频繁, 请稍侯再次发布')));
        }
    }

    $comment = array(
        'cid' => $self->request->cid,
        'created' => $options->gmtTime,
        'agent' => $self->request->getAgent(),
        'ip' => $self->request->getIp(),
        'ownerId' => 1,
        'type' => 'comment',
        'status' => $options->commentsRequireModeration ? 'waiting' : 'approved'
    );

    /** 判断父节点 */
    if ($parentId = $self->request->filter('int')->get('parent')) {
        if ($options->commentsThreaded && ($parent = $db->fetchRow($db->select('coid', 'cid')->from('table.comments')->where('coid = ?', $parentId))) && $self->request->cid == $parent['cid']) {
            $comment['parent'] = $parentId;
        } else {
            $self->response->throwJson(array('status' => 0, 'msg' => _t('父级评论不存在')));
        }
    }
    $feedback = Typecho_Widget::widget('Widget_Feedback');
    //检验格式
    $validator = new Typecho_Validate();
    $validator->addRule('author', 'required', _t('必须填写用户名'));
    $validator->addRule('author', 'xssCheck', _t('请不要在用户名中使用特殊字符'));
    $validator->addRule('author', array($feedback, 'requireUserLogin'), _t('您所使用的用户名已经被注册,请登录后再次提交'));
    $validator->addRule('author', 'maxLength', _t('用户名最多包含200个字符'), 200);

    if ($options->commentsRequireMail && !$user->hasLogin()) {
        $validator->addRule('mail', 'required', _t('必须填写电子邮箱地址'));
    }

    $validator->addRule('mail', 'email', _t('邮箱地址不合法'));
    $validator->addRule('mail', 'maxLength', _t('电子邮箱最多包含200个字符'), 200);

    if ($options->commentsRequireURL && !$user->hasLogin()) {
        $validator->addRule('url', 'required', _t('必须填写个人主页'));
    }
    $validator->addRule('url', 'url', _t('个人主页地址格式错误'));
    $validator->addRule('url', 'maxLength', _t('个人主页地址最多包含200个字符'), 200);

    $validator->addRule('text', 'required', _t('必须填写评论内容'));

    $comment['text'] = $self->request->text;

    /** 对一般匿名访问者,将用户数据保存一个月 */

    if (!$user->hasLogin()) {
        /** Anti-XSS */
        $comment['author'] = $self->request->filter('trim')->author;
        $comment['mail'] = $self->request->filter('trim')->mail;
        $comment['url'] = $self->request->filter('trim')->url;

        /** 修正用户提交的url */
        if (!empty($comment['url'])) {
            $urlParams = parse_url($comment['url']);
            if (!isset($urlParams['scheme'])) {
                $comment['url'] = 'http://' . $comment['url'];
            }
        }

        $expire = $options->gmtTime + $options->timezone + 30 * 24 * 3600;
        Typecho_Cookie::set('__typecho_remember_author', $comment['author'], $expire);
        Typecho_Cookie::set('__typecho_remember_mail', $comment['mail'], $expire);
        Typecho_Cookie::set('__typecho_remember_url', $comment['url'], $expire);
    } else {
        $comment['author'] = $user->screenName;
        $comment['mail'] = $user->mail;
        $comment['url'] = $user->url;

        /** 记录登录用户的id */
        $comment['authorId'] = $user->uid;
    }

    /** 如果系统设置为不需要审核，并且评论者之前须有评论通过了审核 */
    if (!$options->commentsRequireModeration && $options->commentsWhitelist) {
        if ($feedback->size($feedback->select()->where('author = ? AND mail = ? AND status = ?', $comment['author'], $comment['mail'], 'approved'))) {
            $comment['status'] = 'approved'; // 评论者之前有评论通过了审核设置为审核通过
        } else {
            $comment['status'] = 'waiting'; //  没有的话评论设置为待审核
        }
    }

    if ($error = $validator->run($comment)) {
        $self->response->throwJson(array('status' => 0, 'msg' => implode(';', $error)));
    }

    /** 添加评论 */
    $commentId = $feedback->insert($comment);
    if (!$commentId) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('评论失败')));
    }
    Typecho_Cookie::delete('__typecho_remember_text');
    $db->fetchRow($feedback->select()->where('coid = ?', $commentId)
        ->limit(1), array($feedback, 'push'));
    // 返回评论数据
    $data = array(
        'cid' => $feedback->cid,
        'coid' => $feedback->coid,
        'parent' => $feedback->parent,
        'mail' => $feedback->mail,
        'url' => $feedback->url,
        'ip' => $feedback->ip,
        'agent' => $feedback->agent,
        'author' => $feedback->author,
        'authorId' => $feedback->authorId,
        'permalink' => $feedback->permalink,
        'created' => $feedback->created,
        'datetime' => $feedback->date->format('Y-m-d H:i:s'),
        'status' => $feedback->status,
    );
    // 评论内容
    ob_start();
    $feedback->content();
    $data['content'] = ob_get_clean();

    $data['avatar'] = Typecho_Common::gravatarUrl($data['mail'], 48, Helper::options()->commentsAvatarRating, NULL, $self->request->isSecure());
    $self->response->throwJson(array('status' => 1, 'comment' => $data));
}

/**
 * 点赞
 */
function addAgree($self)
{
    $self->response->setStatus(200);

    $cid = intval($self->request->cid);
    $isAgree = intval($self->request->agree);

    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();

    // 检查字段
    $checkRow = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $cid)->limit(1));
    if (!$checkRow) {
        $checkRow = $db->fetchRow($db->select()->from('table.contents')->limit(1));
    }
    if ($checkRow && !array_key_exists('agree', $checkRow)) {
        try {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
        } catch (Exception $e) {}
    }
    if ($checkRow && !array_key_exists('agree_users', $checkRow)) {
        try {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree_users` TEXT;');
        } catch (Exception $e) {}
    }

    // 根据文章 cid 查询出点赞信息
    $agreeRow = $db->fetchRow($db->select('agree', 'agree_users')->from('table.contents')->where('cid = ?', $cid));
    if (!$agreeRow) {
        return $self->response->throwJson(array('status' => 0, 'msg' => '文章不存在'));
    }

    $curAgree = intval($agreeRow['agree'] ?? 0);
    $names = parseAgreeUsers($agreeRow['agree_users'] ?? '');

    // 获取当前点赞者昵称
    $user = Typecho_Widget::widget('Widget_User');
    $author = trim(strval($self->request->author ?: ''));
    $mail = trim(strval($self->request->mail ?: ''));

    if ($user->hasLogin()) {
        $likerName = trim(strval($user->screenName ?: $user->name));
    } else if (!empty($author)) {
        $likerName = $author;
    } else {
        $likerName = '';
    }

    // 获取点赞 Cookie 记录
    $agreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
    $agreeList = [];
    if (!empty($agreeRecording)) {
        $decoded = json_decode($agreeRecording, true);
        if (is_array($decoded)) {
            $agreeList = $decoded;
        }
    }

    $num = 0;
    if ($isAgree === 1) {
        // 点赞
        if (!in_array($cid, $agreeList)) {
            $agreeList[] = $cid;
            Typecho_Cookie::set('typechoAgreeRecording', json_encode(array_values(array_unique($agreeList))));
            $num = 1;
        }
        if (!empty($likerName) && !in_array($likerName, $names, true)) {
            $names[] = $likerName;
        }
    } else {
        // 取消点赞
        if (in_array($cid, $agreeList)) {
            $agreeList = array_diff($agreeList, [$cid]);
            Typecho_Cookie::set('typechoAgreeRecording', json_encode(array_values($agreeList)));
            $num = -1;
        }
        if (!empty($likerName)) {
            $names = array_values(array_filter($names, function ($n) use ($likerName) {
                return $n !== $likerName;
            }));
        }
    }

    $newAgree = max(0, $curAgree + $num);
    if ($newAgree === 0) {
        $names = [];
    }

    $db->query($db->update('table.contents')->rows(array(
        'agree' => $newAgree,
        'agree_users' => json_encode($names, JSON_UNESCAPED_UNICODE)
    ))->where('cid = ?', $cid));

    $contentHtml = renderAgreeContentHtml($cid, $newAgree, $names);

    return $self->response->throwJson(array(
        'status' => 1,
        'agree' => $newAgree,
        'agree_users' => $names,
        'agree_content_html' => $contentHtml
    ));
}

/**
 * 根据用户id获取用户头像地址
 */
function getUserAvatar($authorId)
{
    $db = Typecho_Db::get();
    $options = Helper::options();

    $user = $db->fetchRow($db->select('table.users.mail')->from('table.users')->where('uid = ?', $authorId)->limit(1));

    $mail = $user['mail'];

    $avatarSource = "https://cravatar.cn/avatar/";
    if (!empty($options->avatarSource)) {
        $avatarSource = $options->avatarSource;
    }

    $gravatarUrl = $avatarSource . md5(strtolower(trim($mail))) . "?s=64&d=identicon";

    return $gravatarUrl;
}

/**
 * 前端用户注册
 */
function apiRegister($self)
{
    $self->response->setStatus(200);
    $options = Helper::options();
    $user = Typecho_Widget::widget('Widget_User');
    $db = Typecho_Db::get();

    if ($user->hasLogin()) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('您已处于登录状态')));
    }

    $name = trim($self->request->name);
    $screenName = trim($self->request->screenName);
    $mail = trim($self->request->mail);
    $password = $self->request->password;
    $confirm = $self->request->confirm;

    if (empty($name)) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('必须填写用户名')));
    }
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 32) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('用户名长度需在2至32个字符之间')));
    }
    // 特殊字符检查
    if (!preg_match("/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_\-\.]+$/u", $name)) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('用户名仅支持中文、字母、数字、下划线及点')));
    }

    // 处理用户自定义个性昵称
    if (empty($screenName)) {
        $screenName = $name;
    } else {
        $screenName = strip_tags($screenName);
        if (mb_strlen($screenName, 'UTF-8') > 32) {
            $screenName = mb_substr($screenName, 0, 32, 'UTF-8');
        }
    }

    if (empty($mail)) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('必须填写电子邮箱')));
    }
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('电子邮箱格式不正确')));
    }

    if (empty($password) || strlen($password) < 6) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('密码至少包含6位字符')));
    }
    if (!empty($confirm) && $password !== $confirm) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('两次输入的密码不一致')));
    }

    // 检查用户名是否已存在
    $nameExists = $db->fetchRow($db->select('uid')->from('table.users')->where('name = ?', $name)->limit(1));
    if ($nameExists) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('该用户名已被注册，请更换或直接登录')));
    }

    // 检查邮箱是否已存在
    $mailExists = $db->fetchRow($db->select('uid')->from('table.users')->where('mail = ?', $mail)->limit(1));
    if ($mailExists) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('该邮箱已被注册，请直接登录')));
    }

    $hasher = new \Utils\PasswordHash(8, true);
    $dataStruct = array(
        'name' => $name,
        'mail' => $mail,
        'screenName' => $screenName,
        'password' => $hasher->hashPassword($password),
        'created' => $options->time,
        'group' => 'subscriber'
    );

    try {
        $insertId = $db->query($db->insert('table.users')->rows($dataStruct));
        if (!$insertId) {
            $self->response->throwJson(array('status' => 0, 'msg' => _t('注册失败，请稍后重试')));
        }
    } catch (\Exception $e) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('注册异常: ' . $e->getMessage())));
    }

    // 自动登录
    $user->login($name, $password, false, 30 * 24 * 3600);

    $avatar = getUserAvatar($insertId);

    $self->response->throwJson(array(
        'status' => 1,
        'msg' => _t('注册成功，已自动登录！'),
        'user' => array(
            'uid' => $insertId,
            'name' => $name,
            'screenName' => $screenName,
            'mail' => $mail,
            'url' => '',
            'group' => 'subscriber',
            'avatar' => $avatar
        )
    ));
}

/**
 * 前端用户登录
 */
function apiLogin($self)
{
    $self->response->setStatus(200);
    $user = Typecho_Widget::widget('Widget_User');
    $db = Typecho_Db::get();

    if ($user->hasLogin()) {
        $self->response->throwJson(array(
            'status' => 1,
            'msg' => _t('已登录'),
            'user' => array(
                'uid' => $user->uid,
                'name' => $user->name,
                'screenName' => $user->screenName,
                'mail' => $user->mail,
                'url' => $user->url,
                'group' => $user->group,
                'avatar' => getUserAvatar($user->uid)
            )
        ));
    }

    $name = trim($self->request->name);
    $password = $self->request->password;

    if (empty($name) || empty($password)) {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('请输入用户名和密码')));
    }

    // 支持邮箱登录
    if (strpos($name, '@') !== false) {
        $userRow = $db->fetchRow($db->select('name')->from('table.users')->where('mail = ?', $name)->limit(1));
        if ($userRow) {
            $name = $userRow['name'];
        }
    }

    $valid = $user->login($name, $password, false, 30 * 24 * 3600);
    if ($valid) {
        $self->response->throwJson(array(
            'status' => 1,
            'msg' => _t('登录成功！'),
            'user' => array(
                'uid' => $user->uid,
                'name' => $user->name,
                'screenName' => $user->screenName,
                'mail' => $user->mail,
                'url' => $user->url,
                'group' => $user->group,
                'avatar' => getUserAvatar($user->uid)
            )
        ));
    } else {
        $self->response->throwJson(array('status' => 0, 'msg' => _t('用户名或密码错误')));
    }
}

/**
 * 前端用户退出
 */
function apiLogout($self)
{
    $self->response->setStatus(200);
    $user = Typecho_Widget::widget('Widget_User');
    if ($user->hasLogin()) {
        $user->logout();
    }
    $self->response->throwJson(array('status' => 1, 'msg' => _t('已安全退出登录')));
}

/**
 * 获取网易云音乐详情（健壮容错，永不阻断外链生成）
 */
function apiGetMusicInfo($self)
{
    $self->response->setStatus(200);
    $self->response->setHeader('Access-Control-Allow-Origin', '*');
    $self->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $self->response->setHeader('Content-Type', 'application/json; charset=utf-8');

    $rawInput = trim($self->request->get('song') ?: ($self->request->get('id') ?: ''));
    if (empty($rawInput)) {
        $rawInput = isset($_POST['song']) ? trim($_POST['song']) : (isset($_GET['song']) ? trim($_GET['song']) : '');
    }
    if (empty($rawInput)) {
        $rawInput = isset($_POST['id']) ? trim($_POST['id']) : (isset($_GET['id']) ? trim($_GET['id']) : '');
    }

    if (empty($rawInput)) {
        $self->response->throwJson(array('status' => 0, 'msg' => '请输入网易云歌曲ID或歌曲链接'));
    }

    $songId = '';
    if (preg_match('/id=(\d+)/i', $rawInput, $matches)) {
        $songId = $matches[1];
    } elseif (preg_match('/song\/(\d+)/i', $rawInput, $matches)) {
        $songId = $matches[1];
    } elseif (preg_match('/^\d+$/', $rawInput)) {
        $songId = $rawInput;
    } elseif (preg_match('/(\d{4,12})/', $rawInput, $matches)) {
        $songId = $matches[1];
    }

    if (empty($songId)) {
        $self->response->throwJson(array('status' => 0, 'msg' => '未能识别有效的网易云音乐ID'));
    }

    $audioUrl = "https://music.163.com/song/media/outer/url?id={$songId}.mp3";
    $apiUrl = "https://music.163.com/api/song/detail/?id={$songId}&ids=%5B{$songId}%5D";

    $response = false;
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Referer: https://music.163.com/'));
        $response = curl_exec($ch);
        curl_close($ch);
    }
    if (!$response && function_exists('file_get_contents')) {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'timeout' => 4,
                'header' => "User-Agent: Mozilla/5.0\r\nReferer: https://music.163.com/\r\n"
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            )
        );
        $context = stream_context_create($opts);
        $response = @file_get_contents($apiUrl, false, $context);
    }

    $title = '网易云音乐 (' . $songId . ')';
    $artist = '网易音乐人';
    $pic = '';

    if ($response) {
        $json = json_decode($response, true);
        if (isset($json['songs']) && !empty($json['songs'])) {
            $song = $json['songs'][0];
            if (!empty($song['name'])) {
                $title = $song['name'];
            }
            if (!empty($song['artists']) && is_array($song['artists'])) {
                $artists = array();
                foreach ($song['artists'] as $art) {
                    if (!empty($art['name'])) {
                        $artists[] = $art['name'];
                    }
                }
                if (!empty($artists)) {
                    $artist = implode(' / ', $artists);
                }
            }
            if (isset($song['album']['picUrl']) && !empty($song['album']['picUrl'])) {
                $pic = $song['album']['picUrl'];
            } elseif (isset($song['artists'][0]['picUrl']) && !empty($song['artists'][0]['picUrl'])) {
                $pic = $song['artists'][0]['picUrl'];
            }
        }
    }

    $self->response->throwJson(array(
        'status' => 1,
        'data' => array(
            'id' => $songId,
            'title' => $title,
            'artist' => $artist,
            'pic' => $pic,
            'url' => $audioUrl
        )
    ));
}

/**
 * 删除已上传的附件（用于发布前取消上传，或编辑时移除图片）
 */
function apiDeleteAttachment($self)
{
    $self->response->setStatus(200);
    $user = Typecho_Widget::widget('Widget_User');
    if (!$user->hasLogin()) {
        $self->response->throwJson(['status' => 0, 'msg' => '请先登录']);
    }

    $cid = (int)$self->request->filter('int')->get('cid', 0);
    $url = trim((string)$self->request->get('url', ''));

    if ($cid <= 0 && empty($url)) {
        $self->response->throwJson(['status' => 0, 'msg' => '缺少 cid 或 url 参数']);
    }

    $db = Typecho_Db::get();
    $pathsToDelete = [];
    $attachmentCids = [];

    if ($cid > 0) {
        $row = $db->fetchRow($db->select('cid', 'authorId', 'type', 'text')
            ->from('table.contents')
            ->where('cid = ? AND type = ?', $cid, 'attachment')
            ->limit(1));
        if (!empty($row)) {
            // 权限检查：非管理员只能删除自己上传的文件
            if (!$user->pass('editor', true) && $row['authorId'] != $user->uid) {
                $self->response->throwJson(['status' => 0, 'msg' => '无权删除该附件']);
            }
            $attachmentCids[] = (int)$row['cid'];
            $data = @unserialize($row['text']);
            if (is_array($data) && !empty($data['path'])) {
                $pathsToDelete[] = $data['path'];
            }
        }
    }

    if (!empty($url)) {
        $pathsToDelete[] = $url;
        // 查找可能匹配该 url 的附件记录
        $matchedRows = $db->fetchAll($db->select('cid', 'authorId')
            ->from('table.contents')
            ->where('type = ?', 'attachment')
            ->where('text LIKE ?', '%' . $url . '%'));
        if (!empty($matchedRows)) {
            foreach ($matchedRows as $mr) {
                if ($user->pass('editor', true) || $mr['authorId'] == $user->uid) {
                    $attachmentCids[] = (int)$mr['cid'];
                }
            }
        }
    }

    $uploadRootDir = defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__;

    // 执行物理删除
    foreach (array_unique($pathsToDelete) as $targetPath) {
        // 公共版：仅删除网站服务器本地文件（原生上传路径），不涉及外部存储
        $sub = $targetPath;
        $pos = strpos($sub, 'onenote/');
        if ($pos !== false) {
            $sub = substr($sub, $pos + strlen('onenote/'));
        }
        $posUpload = strpos($sub, 'usr/uploads/');
        if ($posUpload !== false) {
            $sub = substr($sub, $posUpload + strlen('usr/uploads/'));
        }
        $sub = ltrim($sub, '/\\');

        $localCandidates = [
            rtrim($uploadRootDir, '/\\') . '/' . ltrim($targetPath, '/\\'),
            rtrim(__TYPECHO_ROOT_DIR__, '/\\') . '/' . ltrim($targetPath, '/\\'),
            rtrim($uploadRootDir, '/\\') . '/usr/uploads/' . $sub,
            rtrim(__TYPECHO_ROOT_DIR__, '/\\') . '/usr/uploads/' . $sub,
        ];
        foreach (array_unique($localCandidates) as $lp) {
            if (is_file($lp)) {
                @unlink($lp);
            }
        }
    }

    // 从数据库中删除附件记录及评论
    if (!empty($attachmentCids)) {
        foreach (array_unique($attachmentCids) as $attCid) {
            $db->query($db->delete('table.comments')->where('cid = ?', $attCid));
            $db->query($db->delete('table.contents')->where('cid = ? AND type = ?', $attCid, 'attachment'));
        }
    }

    $self->response->throwJson(['status' => 1, 'msg' => '删除成功']);
}

/**
 * 全站文章搜索接口（2026-10-23）
 * GET /api/search?q=关键词（或 ?api=search&q=...）
 * 返回：标题 + 正文摘要（关键词上下文窗口）+ 日期 + 永久链接
 * SQLite LIKE 全文匹配，文章量级 < 1000 时性能足够；数量上限 20 条。
 */
function apiSearchContents($self)
{
    $self->response->setStatus(200);
    $self->response->setHeader('Access-Control-Allow-Origin', '*');
    $self->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $self->response->setHeader('Content-Type', 'application/json; charset=utf-8');

    $keyword = isset($_GET['q']) ? trim((string) $_GET['q']) : (isset($_POST['q']) ? trim((string) $_POST['q']) : '');
    if ($keyword === '') {
        $self->response->throwJson(array('status' => 0, 'msg' => '请输入搜索关键词', 'list' => array()));
    }
    if (mb_strlen($keyword, 'UTF-8') > 50) {
        $keyword = mb_substr($keyword, 0, 50, 'UTF-8');
    }

    $db = Typecho_Db::get();
    // SQLite 的 LIKE 默认不支持 ESCAPE 转义，直接把通配符从关键词里剔除
    // （个人博客搜索场景下 % _ 无实义），其余字符由查询构建器参数化处理，无注入风险
    $escaped = str_replace(array('%', '_'), '', $keyword);
    $like = '%' . $escaped . '%';

    $rows = $db->fetchAll($db->select('cid', 'title', 'text', 'slug', 'created')
        ->from('table.contents')
        ->where('type = ?', 'post')
        ->where('status = ?', 'publish')
        ->where('title LIKE ? OR text LIKE ?', $like, $like)
        ->order('created', Typecho_Db::SORT_DESC)
        ->limit(20));

    $list = array();
    foreach ($rows as $row) {
        // 正文转可读文本：去 markdown/HTML 标记与附件语法
        $plain = (string) $row['text'];
        $plain = preg_replace('/<!--.*?-->/s', ' ', $plain);
        $plain = preg_replace('/!\[[^\]]*\]\([^)]*\)/', ' ', $plain);
        $plain = preg_replace('/<[^>]+>/', ' ', $plain);
        $plain = html_entity_decode($plain, ENT_QUOTES, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', trim($plain));

        // 摘要：取关键词首次出现位置前后各约 70 字
        $pos = mb_stripos($plain, $keyword, 0, 'UTF-8');
        if ($pos === false) {
            $snippet = mb_substr($plain, 0, 110, 'UTF-8');
        } else {
            $start = max(0, $pos - 70);
            $snippet = ($start > 0 ? '…' : '') . mb_substr($plain, $start, 150, 'UTF-8');
            if ($start + 150 < mb_strlen($plain, 'UTF-8')) {
                $snippet .= '…';
            }
        }
        // 标题为空时（朋友圈随笔常见）用正文开头补
        $title = trim((string) $row['title']);
        if ($title === '') {
            $title = $plain !== '' ? mb_substr($plain, 0, 24, 'UTF-8') : '无标题动态';
        }

        // 永久链接：走 Typecho 路由表生成，失败则按当前自定义规则兜底 {cid}.html
        try {
            $url = Typecho_Router::url('post', array(
                'cid' => $row['cid'],
                'slug' => $row['slug'],
            ), Helper::options()->index);
        } catch (Throwable $e) {
            $url = rtrim(Helper::options()->siteUrl, '/') . '/' . $row['cid'] . '.html';
        }

        $list[] = array(
            'cid' => (int) $row['cid'],
            'title' => $title,
            'snippet' => $snippet,
            'date' => date('Y-m-d', (int) $row['created']),
            'url' => $url,
        );
    }

    $self->response->throwJson(array('status' => 1, 'total' => count($list), 'list' => $list));
}
?>
<?php
/**
 * Copyright (C) 2013 ChinqHDTV@cnhd.com All Right Reserved.
 * Synology DLM Guide @see http://ukdl.synology.com/download/ds/userguide/DLM_Guide.pdf
 */

class SynoDLMSearchCNHD {
  private $qurl = 'http://hd.gg/torrentrss.php?search=%s';
  private $qurl2 = 'http://hd.gg/torrentrss.php?search=%s&linktype=dl&passkey=%s';
  private $loginUrl = 'http://hd.gg/takelogin.php';
  private $passkeyGenUrl = 'http://hd.gg/getrss.php';
  private $cookies = '/tmp/cnhd.cookies.txt';
  private $passkey = '';

  public function __construct() {
  }

  /**
   * Synology 搜索模块系统接口，必须实现
   * @param $curl   系统提供的curl实例
   * @param $query   查询字符串
   * @param $username  启用登录功能账户名
   * @param $password  启用登录功能密码
   */
  public function prepare($curl, $query, $username, $password) {
    if ($username != NULL && $password != NULL) {
      if ($this->VerifyAccount($username, $password) && $this->genPasskey()) {
        $url = sprintf($this->qurl2, urlencode($query), $this->passkey);
      }
      else {
        $url = sprintf($this->qurl, urlencode($query));
      }

    }
    else {
      $url = sprintf($this->qurl, urlencode($query));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
  }

  /**
   * Synology 搜索模块系统接口，不是必须实现
   * 这个方法只是为了在账户设置小框验证帐号做的，如果点击 验证 ，此方法返回 TRUE 就提示成功，否则提示失败
   * 本插件既支持该操作 ，也依赖该方法，请勿抛弃它
   * 登录功能
   * @param $username
   * @param $password
   * @return bool
   */
  public function VerifyAccount($username, $password) {

    if (file_exists($this->cookies)) {
      unlink($this->cookies);
    } //删除cookies 以防止旧cookies导致不能登录

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:14.0) Gecko/20100101 Firefox/14.0.1');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookies);
    curl_setopt($curl, CURLOPT_URL, $this->loginUrl);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, array('username' => trim($username), 'password' => trim($password)));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $Result = curl_exec($curl);
    curl_close($curl);

    //登录成功才会有退出的吧，找退出链接
    if (FALSE != strpos($Result, "logout.php")) {
      return TRUE;
    }

    return FALSE;

  }

  /**
   *
   * 通过rss生成页面获取passkey
   * @return bool
   */
  public function genPasskey() {
    if (!file_exists($this->cookies)) {
      return FALSE;
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:14.0) Gecko/20100101 Firefox/14.0.1');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookies);
    curl_setopt($curl, CURLOPT_URL, $this->passkeyGenUrl);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, 'inclbookmarked=0&search=&search_mode=1&showrows=10');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $Result = curl_exec($curl);
    curl_close($curl);


    /**
     * $passkeyGenUrl  执行成功将返回如下信息
     *
     * 你可以在RSS阅读器（如Google Reader）使用以下URL：
     * http://www.cnhd.com/torrentrss.php?rows=10
     *
     * 你可以在支持RSS订阅功能的BT客户端（如uTorrent）使用以下URL：
     * http://www.cnhd.com/torrentrss.php?rows=10&linktype=dl&passkey=（32位A-Z a-z 0-9字符）
     *
     * 使用正则提取passkey
     */

    $pattern = '/passkey=[A-Za-z0-9]{32}/';
    if (1 === preg_match($pattern, $Result, $matches)) {
      $this->passkey = substr($matches[0], 8);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Synology 搜索模块系统接口，必须实现
   * @param $plugin    插件实例对象
   * @param $response  curl执行结果content body
   * @return mixed    结果条数
   */
  public function parse($plugin, $response) {
    //插件自带RSS解析功能
    return $plugin->addRSSResults($response);
  }

}

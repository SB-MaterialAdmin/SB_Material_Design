<?php

class CSteamId {
  private static $_cache = [];

  public $IsEmulated = false;

  public $CommunityID;
  public $AccountID;
  public $v2;
  public $v3;

  public static function factory($sid, $GameID = 0) {
    $AccountID = self::ResolveToAccountID($sid);
    if ($AccountID == -1)
      throw new InvalidArgumentException("Invalid SteamID passed.");

    if (!isset(self::$_cache[$AccountID])) {
      $CacheEntry = new self();
      $CacheEntry->CommunityID  = bcadd('76561197960265728', $AccountID, 0);
      $CacheEntry->AccountID    = $AccountID;
      $CacheEntry->v3           = sprintf('[U:1:%d]', $AccountID);
      $CacheEntry->v2           = sprintf('STEAM_%d:%d:%d', $GameID, ($AccountID % 2), $AccountID / 2);

      if ($AccountID > 2147483647) {
        $CacheEntry->IsEmulated = true;
      }

      self::$_cache[$AccountID] = $CacheEntry;
    }

    return self::$_cache[$AccountID];
  }

  public static function validate($sid) {
    return (self::ResolveToAccountID($sid) != -1);
  }

  private static function ResolveToAccountID($sid) {
    if (strncmp('STEAM_', $sid, 6) == 0) {
      $parts = explode(':', $sid);
      if (count($parts) != 3)
        return -1;

      return intval($parts[2] * 2) + intval($parts[1]);
    }

    if (strncmp('[U:1', $sid, 4) == 0) {
      $parts = explode(':', $sid);
      if (count($parts) != 3)
        return -1;

      return intval(substr($parts[2], 0, -1));
    }

    if (strncmp('7656', $sid, 4) == 0 && strlen($sid) == 17) {
      return intval(bcsub($sid, '76561197960265728', 0));
    }

    // try handle as URL.
    if (preg_match('/^https?:\/\/steamcommunity\.com\/profiles\/7656(.{13})\/?$/', $sid, $matches, PREG_OFFSET_CAPTURE))
    {
      return self::ResolveToAccountID('7656' . $matches[1][0]);
    }
    if (preg_match('/^https?:\/\/steamcommunity\.com\/id\/([\w\d-_]{1,})\/?$/', $sid, $matches, PREG_OFFSET_CAPTURE))
    {
      $client = \HTTP::client();
      $response = $client->setUrl('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/')
        ->setBody(sprintf('key=%s&vanityurl=%s', STEAM_API_KEY, $matches[1][0]))->send()->JSON(true);

      if ($response['response']['success'] == 1) return self::ResolveToAccountID($response['response']['steamid']);
    }

    // we don't know, what is this.
    return -1;
  }
}

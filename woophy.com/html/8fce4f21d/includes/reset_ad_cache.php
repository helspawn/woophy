<?php

//not used anymore, advertisements are now delivered by OpenX

require_once CLASS_PATH.'ClassFactory.class.php';
require_once CLASS_PATH.'Advertising.class.php';
?>
<fieldset>
<legend>Reset ad cache</legend>
<br/>
<p class="strong">
<?php

$adv = new Advertising();
//KLUDGE : make constant of memcache key!
if($adv->deleteFromCache('Advertising::getAdsBySizeId'))echo 'Advertisement cache has been reset!';
else echo 'Could not update caches!<br/>(no cache found, or no memcache connection)';
$adv->getAdsBySizeId(0, 0, 1);//reload cache

?>
</p>
</fieldset>
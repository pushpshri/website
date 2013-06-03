<?
namespace Destiny;
use Destiny\Utils\Tpl;
use Destiny\Utils\Lol;
?>
<div id="challengeInvites" class="content content-dark clearfix">

	<table class="grid pull-left" style="width:100%;">
		<thead>
			<tr>
				<td>Recieved</td>
			</tr>
		</thead>
		<tbody>
			<?$invites = Service\Fantasy\Cache::getInstance()->getInvites(Session::$team['teamId'], 5)?>
			<?foreach($invites as $invite):?>
			<?$title = Tpl::out($invite->displayName)?>
			<tr>
				<td style="text-align:left;">
					<button class="btn btn-success btn-mini invite-accept" title="Accept invite" data-teamId="<?=$invite->teamId?>">Accept</button>
					<button class="btn btn-danger btn-mini invite-decline" title="Decline invite" data-teamId="<?=$invite->teamId?>">Decline</button>
					<?=Tpl::flag($invite->country)?>
					<?=Tpl::subIcon($invite->subscriber)?>
					<?=$title?>
				</td>
			</tr>
			<?endforeach;?>
			<?for($s=0;$s<1-count($invites);$s++):?>
			<tr>
				<td><span class="subtle">No challenges received.</span></td>
			</tr>
			<?endfor;?>
		</tbody>
	</table>

	<table class="grid pull-right" style="width:100%;">
		<thead>
			<tr>
				<td>Sent</td>
			</tr>
		</thead>
		<tbody>
			<?$sentInvites = Service\Fantasy\Db\Challenge::getInstance()->getSentInvites(Session::$team['teamId'], 5)?>
			<?foreach($sentInvites as $sent):?>
			<?$title = Tpl::out($sent->displayName)?>
			<tr>
				<td style="text-align:left;">
					<a href="#deletesentinvite" class="sent-invite-delete" title="Delete invite" data-teamId="<?=$sent->teamId?>"><i class="icon-remove icon-white subtle"></i></a>
					<?=Tpl::flag($sent->country)?>
					<?=Tpl::subIcon($sent->subscriber)?>
					<?=$title?>
				</td>
			</tr>
			<?endforeach;?>
			<?for($s=0;$s<1-count($sentInvites);$s++):?>
			<tr>
				<td><span class="subtle">No challenges sent.</span></td>
			</tr>
			<?endfor;?>
		</tbody>
	</table>
	
	<form class="challengeForm clearfix pull-left">
		<div class="input-append">
			<input class="span3" autocomplete="off" name="name" type="text" placeholder="Who do you want to challenge?" />
			<button class="btn btn-inverse" type="submit">Challenge!</button>
		</div>
	</form>
	
</div>
<?php
include_once 'layout-start.php';
$msg='';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['booking_id'],$_POST['status'])){
    $id=(int)$_POST['booking_id'];
    $status=$_POST['status'];
    $allowed=['pending','quoted','confirmed','in_progress','completed','cancelled'];
    if(in_array($status,$allowed,true)){
        $st=$conn->prepare("UPDATE bookings SET status=? WHERE booking_id=?");
        $st->bind_param('si',$status,$id);
        if($st->execute())$msg='Booking status updated.';
        else $err='Could not update booking.';
        $st->close();
    }
}
$rows=$conn->query("SELECT b.booking_id,b.event_date,b.start_time,b.end_time,b.guest_count,b.status,
b.quoted_total,u.full_name,u.email,u.phone,p.title package_title,v.name venue_name,(SELECT method FROM 
payments py WHERE py.booking_id=b.booking_id ORDER BY py.payment_id DESC LIMIT 1) payment_method,(SELECT 
status FROM payments py WHERE py.booking_id=b.booking_id ORDER BY py.payment_id DESC LIMIT 1) 
payment_status FROM bookings b JOIN customers c ON b.customer_id=c.customer_id JOIN users u ON 
c.user_id=u.user_id LEFT JOIN packages p ON b.package_id=p.package_id LEFT JOIN venues v ON 
b.venue_id=v.venue_id ORDER BY b.booking_id DESC");
?>
<section class="page-heading">
    <div>
        <h1>Bookings Management</h1>
        <p>Review customer bookings and update their progress.</p>
    </div>
    <span class="date-label">
        Total: <?=$rows?$rows->num_rows:0?>
    </span>
</section>
<?php if($msg):?>
<div class="notice">
    <?=h($msg)?>
</div>
<?php endif;?>
<?php if($err):?>
<div class="notice error">
    <?=h($err)?>
</div>
<?php endif;?>
<section class="panel table-panel">
    <div class="table-scroll">
        <table id="bookingsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Package / Venue</th>
                    <th>Event date</th>
                    <th>Guests</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status / Update</th>
                </tr>
            </thead>
            <tbody>
            <?php if($rows&&$rows->num_rows):while($r=$rows->fetch_assoc()):?>
                <tr>
                    <td>#<?=$r['booking_id']?></td>
                    <td><b><?=h($r['full_name'])?></b><span class="subtext"><?=h($r['email'])?><br><?=h($r['phone'])?></span></td><td><?=h($r['package_title']??'—')?><span class="subtext"><?=h($r['venue_name']??'—')?></span></td><td><?=h($r['event_date'])?><span class="subtext"><?=h($r['start_time'])?> – <?=h($r['end_time'])?></span></td><td><?=$r['guest_count']?></td><td><?=money($r['quoted_total'])?></td><td><?=h($r['payment_method']??'—')?><span class="subtext"><?=h($r['payment_status']??'')?></span></td><td><form method="post" class="btnrow"><input type="hidden" name="booking_id" value="<?=$r['booking_id']?>"><select class="form-control" name="status" style="width:auto;margin:0"><?php foreach(['pending','quoted','confirmed','in_progress','completed','cancelled'] as $s):?><option value="<?=$s?>" <?=$r['status']===$s?'selected':''?>><?=ucwords(str_replace('_',' ',$s))?></option><?php endforeach;?></select><button class="btn">Save</button></form></td></tr><?php endwhile;else:?><tr><td colspan="8" class="empty-row">No bookings found.</td></tr><?php endif;?></tbody></table></div></section>
<script>document.getElementById('dashboardSearch')?.addEventListener('input',function(){let q=this.value.toLowerCase();document.querySelectorAll('#bookingsTable tbody tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none')});</script>
<?php require __DIR__.'/layout-end.php'; ?>
<?php
include_once 'layout-start.php';
$rows=$conn->query("SELECT u.user_id,u.full_name,u.email,u.phone,u.status,c.address,
COUNT(DISTINCT b.booking_id) booking_count,COALESCE(SUM(CASE WHEN py.status='completed' 
THEN py.amount ELSE 0 END),0) paid FROM users u JOIN customers c ON c.user_id=u.user_id 
LEFT JOIN bookings b ON b.customer_id=c.customer_id LEFT JOIN payments py ON 
py.booking_id=b.booking_id WHERE u.role='customer' GROUP BY u.user_id,u.full_name,u.email,
u.phone,u.status,c.address ORDER BY u.user_id DESC");
?>
<style>
    .button-container{
        display: flex;
        justify-content: flex-end;
        margin-top: 15px;
    }
    .new-btn {
    border: none;
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    background: #16a34a;
    color: white;
}
</style>
<section class="page-heading">
    <div>
        <h1>Customers</h1>
        <p>Registered customer accounts and booking history.</p>
    </div>
    <span class="date-label">
        Total: <?=$rows?$rows->num_rows:0?>
    </span>
</section>
<section class="panel table-panel">
    <div class="table-scroll">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Bookings</th>
                    <th>Paid Amount</th>
                    <th>Account Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($rows&&$rows->num_rows):while($r=$rows->fetch_assoc()):?>
                <tr>
                    <td>
                        <b><?=h($r['full_name'])?></b>
                        <span class="subtext">#<?=$r['user_id']?></span>
                    </td>
                    <td><?=h($r['email'])?>
                    <span class="subtext"><?=h($r['phone'])?></span>
                    </td>
                    <td><?=h($r['address']??'—')?></td>
                    <td><?=$r['booking_count']?></td>
                    <td><?=money($r['paid'])?></td>
                    <td>
                        <span class="status <?=h($r['status'])?>">
                            <?=h(ucfirst($r['status']))?>
                        </span>
                    </td>
                </tr>
                <?php endwhile;else:?>
                <tr>
                    <td colspan="6" class="empty-row">
                        No customer accounts found.
                    </td>
                </tr>
                <?php endif;?>
            </tbody>
        </table>
    </div>
</section>
<div class="button-container">
    <button type="submit" class="new-btn"><a href="new_customers.php">+ New </a></button>
</div>
<script>
document.getElementById('dashboardSearch')?.addEventListener('input',function(){
let q=this.value.toLowerCase();
document.querySelectorAll('#dataTable tbody tr').forEach(r=>r.style.display
=r.innerText.toLowerCase().includes(q)?'':'none')
});
</script>
<?php include 'layout-end.php'; ?>
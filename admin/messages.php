<?php
include_once 'layout-start.php';
$rows=$conn->query("SELECT r.review_id,r.rating,r.comment,r.created_at,u.full_name,p.title 
FROM reviews r JOIN customers c ON r.customer_id=c.customer_id JOIN users u ON c.user_id=u.user_id 
LEFT JOIN bookings b ON r.booking_id=b.booking_id LEFT JOIN packages p ON b.package_id=p.package_id 
ORDER BY r.review_id DESC");
?>
<section class="page-heading">
    <div>
        <h1>Messages & Reviews</h1>
        <p>Customer feedback submitted after bookings.</p>
    </div>
    <span class="date-label">Reviews: <?=$rows?$rows->num_rows:0?></span>
</section>
<section class="panel">
    <div class="table-scroll">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if($rows&&$rows->num_rows):while($r=$rows->fetch_assoc()):?>
                <tr>
                    <td><?=h($r['full_name'])?></td>
                    <td><?=h($r['title']??'—')?></td>
                    <td><?=str_repeat('★',(int)$r['rating'])?></td>
                    <td><?=h($r['comment'])?></td>
                    <td><?=h($r['created_at'])?></td>
                </tr>
                <?php endwhile;else:?>
                <tr>
                    <td colspan="5" class="empty-row">
                        No customer reviews yet. The current schema does not contain a separate contact-message table.
                    </td>
                </tr>
                <?php endif;?>
            </tbody>
        </table>
    </div>
</section>
<?php include 'layout-end.php'; ?>
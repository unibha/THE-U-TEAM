<?php
require_once 'db.php';
require_once 'notification_helper.php';

if (isset($_SESSION['user_id'])) {
    $myId = $_SESSION['user_id'];
    
    // Fetch unread count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$myId]);
    $unreadCount = $stmt->fetchColumn();

    // Fetch latest 5 for dropdown
    $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$myId]);
    $recentNotifs = $stmt->fetchAll();

    // Check for urgent popup
    $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = ? AND is_urgent = 1 AND is_read = 0 LIMIT 1");
    $stmt->execute([$myId]);
    $urgentNotif = $stmt->fetch();
}
?>

<style>
    .notif-wrapper { position: relative; cursor: pointer; }
    .notif-badge { 
        position: absolute; top: -8px; right: -8px; 
        background: #f43f5e; color: #fff; font-size: 0.65rem; 
        padding: 2px 6px; border-radius: 50%; font-weight: 800; 
        border: 2px solid #1e293b; 
    }
    .notif-dropdown { 
        position: absolute; top: 50px; right: 0; width: 320px; 
        background: #fff; border-radius: 20px; 
        box-shadow: 0 15px 50px rgba(0,0,0,0.15); 
        display: none; z-index: 1000; border: 1px solid #f1f5f9; 
    }
    .notif-header { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .notif-item { padding: 15px 20px; border-bottom: 1px solid #f8fafc; transition: 0.2s; text-decoration: none; display: block; }
    .notif-item:hover { background: #f8fafc; }
    .notif-item.unread { background: #f0f9ff; }
    .notif-item .title { font-weight: 700; color: #1e293b; font-size: 0.85rem; margin-bottom: 4px; display: block; }
    .notif-item .time { font-size: 0.75rem; color: #94a3b8; font-weight: 600; }
    
    .urgent-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .urgent-modal { background: #fff; width: 90%; max-width: 450px; border-radius: 30px; padding: 40px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.3); }
</style>

<script>
function toggleNotifDropdown(event) {
    event.stopPropagation();
    const dd = document.getElementById('notifDropdown');
    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
}

function dismissUrgent(id) {
    fetch('notifications.php?ajax_read=' + id).then(() => {
        document.getElementById('urgentModal').style.display = 'none';
    });
}

window.onclick = function(event) {
    const dd = document.getElementById('notifDropdown');
    if (dd && !event.target.closest('.notif-wrapper')) {
        dd.style.display = 'none';
    }
}
</script>

<?php if (isset($urgentNotif) && $urgentNotif): ?>
    <div class="urgent-modal-overlay" id="urgentModal">
        <div class="urgent-modal">
            <div style="width: 70px; height: 70px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 25px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 style="font-size: 1.5rem; color: #1e293b; font-weight: 800; margin-bottom: 15px;"><?php echo htmlspecialchars($urgentNotif['title']); ?></h2>
            <p style="color: #64748b; font-weight: 500; line-height: 1.6; margin-bottom: 30px;"><?php echo htmlspecialchars($urgentNotif['message']); ?></p>
            <button onclick="dismissUrgent(<?php echo $urgentNotif['id']; ?>)" style="background: #ef4444; color: #fff; border: none; padding: 15px 40px; border-radius: 15px; font-weight: 800; cursor: pointer; width: 100%; transition: 0.3s; box-shadow: 0 10px 15px rgba(239, 68, 68, 0.2);">Acknowledge & Dismiss</button>
        </div>
    </div>
<?php endif; ?>

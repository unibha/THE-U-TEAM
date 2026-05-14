<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// All logged in users can view
checkAuth(['Admin', 'Teacher', 'Student']);

$role = $_SESSION['role'];
$pageTitle = "Notice Board - Academic Management System";
include_once '../includes/header.php';

// Filter logic
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';

try {
    $query = "SELECT * FROM notice WHERE publish_date <= CURRENT_DATE";
    $params = [];

    // Filter by role
    if ($role === 'Student') {
        $query .= " AND (target_audience = 'All' OR target_audience = 'Student')";
    } elseif ($role === 'Teacher') {
        $query .= " AND (target_audience = 'All' OR target_audience = 'Teacher')";
    }

    if ($search) {
        $query .= " AND (title LIKE ? OR content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($date_filter) {
        $query .= " AND publish_date = ?";
        $params[] = $date_filter;
    }

    // Sort by priority (Urgent first) then by date
    $query .= " ORDER BY CASE WHEN priority = 'Urgent' THEN 1 ELSE 2 END, publish_date DESC, created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $notices = $stmt->fetchAll();
} catch (PDOException $e) {
    $notices = [];
}
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Institutional Portal > Digital Notice Board</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 1000px; margin: 0 auto;">
            
            <section style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h2 style="font-size: 2.2rem; color: #1e293b; font-weight: 800;">Notice Board</h2>
                
                <!-- Search & Filter -->
                <div style="display: flex; gap: 15px;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="noticeSearch" placeholder="Search notices..." style="padding: 12px 15px 12px 45px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; font-weight: 600; width: 250px; outline: none; transition: 0.3s ease;">
                    </div>
                    <input type="date" id="dateFilter" style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; font-weight: 600; outline: none;">
                </div>
            </section>

            <div id="noticeContainer" style="display: flex; flex-direction: column; gap: 30px;">
                <?php if (empty($notices)): ?>
                    <div style="background: #fff; padding: 100px; border-radius: 35px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                        <i class="fas fa-bullhorn" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                        <h3 style="color: #64748b; font-weight: 800; font-size: 1.4rem;">Quiet on the Front</h3>
                        <p style="color: #94a3b8; font-weight: 600;">There are no active announcements matching your filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notices as $n): 
                        $isUrgent = ($n['priority'] === 'Urgent');
                    ?>
                        <div class="notice-card" style="background: #fff; padding: 40px; border-radius: 32px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid <?php echo $isUrgent ? '#fee2e2' : '#f1f5f9'; ?>; position: relative; overflow: hidden;">
                            <?php if ($isUrgent): ?>
                                <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: #ef4444;"></div>
                                <span style="position: absolute; top: 25px; right: 40px; background: #fee2e2; color: #ef4444; padding: 6px 14px; border-radius: 10px; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i> Urgent Alert</span>
                            <?php endif; ?>

                            <div style="margin-bottom: 25px; max-width: 80%;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                    <span style="background: #f1f5f9; color: #64748b; padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800;"><?php echo date('M d, Y', strtotime($n['publish_date'])); ?></span>
                                    <span style="color: #94a3b8; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;">• ID #<?php echo str_pad($n['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <h3 style="font-size: 1.6rem; color: #1e293b; font-weight: 800; line-height: 1.3;"><?php echo htmlspecialchars($n['title']); ?></h3>
                            </div>

                            <div style="color: #475569; font-size: 1.05rem; line-height: 1.7; font-weight: 500; white-space: pre-wrap;"><?php echo htmlspecialchars($n['content']); ?></div>

                            <div style="margin-top: 30px; padding-top: 25px; border-top: 1px solid #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 10px; color: #94a3b8; font-weight: 700; font-size: 0.85rem;">
                                    <i class="fas fa-users-viewfinder"></i>
                                    Visible to: <?php echo $n['target_audience'] === 'All' ? 'Everyone' : $n['target_audience'] . 's'; ?>
                                </div>
                                <div style="color: #cbd5e1; font-size: 0.85rem; font-weight: 600;">
                                    Official Communication
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script>
function refreshNotices() {
    const search = document.getElementById('noticeSearch').value;
    const date = document.getElementById('dateFilter').value;
    const container = document.getElementById('noticeContainer');
    
    fetch(`api_search_notices.php?q=${encodeURIComponent(search)}&date=${encodeURIComponent(date)}`)
        .then(response => {
            if (!response.ok) throw new Error('Search failed');
            return response.json();
        })
        .then(data => {
            container.innerHTML = '';
            
            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div style="background: #fff; padding: 100px; border-radius: 35px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); width: 100%;">
                        <i class="fas fa-bullhorn" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                        <h3 style="color: #64748b; font-weight: 800; font-size: 1.4rem;">Quiet on the Front</h3>
                        <p style="color: #94a3b8; font-weight: 600;">No notices found matching your filters.</p>
                    </div>
                `;
                return;
            }
            
            data.forEach(n => {
                const isUrgent = n.priority === 'Urgent';
                const card = document.createElement('div');
                card.className = 'notice-card';
                card.style.cssText = `background: #fff; padding: 40px; border-radius: 32px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid ${isUrgent ? '#fee2e2' : '#f1f5f9'}; position: relative; overflow: hidden; margin-bottom: 30px;`;
                
                const publishDate = new Date(n.publish_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                const idStr = n.id.toString().padStart(4, '0');

                card.innerHTML = `
                    ${isUrgent ? `
                        <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: #ef4444;"></div>
                        <span style="position: absolute; top: 25px; right: 40px; background: #fee2e2; color: #ef4444; padding: 6px 14px; border-radius: 10px; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i> Urgent Alert</span>
                    ` : ''}

                    <div style="margin-bottom: 25px; max-width: 80%;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <span style="background: #f1f5f9; color: #64748b; padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800;">${publishDate}</span>
                            <span style="color: #94a3b8; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;">• ID #${idStr}</span>
                        </div>
                        <h3 style="font-size: 1.6rem; color: #1e293b; font-weight: 800; line-height: 1.3;">${n.title}</h3>
                    </div>

                    <div style="color: #475569; font-size: 1.05rem; line-height: 1.7; font-weight: 500; white-space: pre-wrap;">${n.content}</div>

                    <div style="margin-top: 30px; padding-top: 25px; border-top: 1px solid #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px; color: #94a3b8; font-weight: 700; font-size: 0.85rem;">
                            <i class="fas fa-users-viewfinder"></i>
                            Visible to: ${n.target_audience === 'All' ? 'Everyone' : n.target_audience + 's'}
                        </div>
                        <div style="color: #cbd5e1; font-size: 0.85rem; font-weight: 600;">
                            Official Communication
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            // Don't clear container if search failed, keep old results
        });
}

document.getElementById('noticeSearch').addEventListener('input', refreshNotices);
document.getElementById('dateFilter').addEventListener('change', refreshNotices);
</script>

<?php include_once '../includes/footer.php'; ?>


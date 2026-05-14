<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Student allowed
checkAuth(['Student']);

$studentUserId = $_SESSION['user_id'];
$pageTitle = "Learning Resources - Student Portal";
include_once '../includes/header.php';

// Fetch student's internal ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$studentUserId]);
$studentInternalId = $stmt->fetchColumn();

// Fetch resources for enrolled courses only
$stmt = $pdo->prepare("
    SELECT r.*, c.course_name, c.course_code, u.first_name, u.last_name 
    FROM resources r 
    JOIN courses c ON r.course_id = c.id 
    JOIN enrollments e ON c.id = e.course_id 
    JOIN users u ON r.uploaded_by = u.id 
    WHERE e.student_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$studentInternalId]);
$availableResources = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Student Portal > Academic Resources</p>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="resourceSearch" placeholder="Search resources...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="student_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 5px;">Study Materials Library</h2>
                <p style="color: #64748b; font-weight: 600;">Access slides, notes, and documents uploaded by your instructors.</p>
            </div>
            <div style="background: #fff; padding: 10px 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #f1f5f9;">
                <span id="resourceCount" style="font-size: 0.85rem; font-weight: 700; color: #8b5cf6;"><i class="fas fa-book-open" style="margin-right: 8px;"></i><?php echo count($availableResources); ?> Resources Available</span>
            </div>
        </div>

        <div id="resourceGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
            <?php if (empty($availableResources)): ?>
                <div style="grid-column: 1/-1; background: #fff; padding: 80px; border-radius: 35px; text-align: center; border: 2px dashed #e2e8f0;">
                    <i class="fas fa-folder-open" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                    <h3 style="color: #64748b; font-weight: 800; font-size: 1.4rem;">No Resources Yet</h3>
                    <p style="color: #94a3b8; font-weight: 600;">Your instructors haven't uploaded any study materials for your enrolled courses yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($availableResources as $res): ?>
                <div class="resource-card" style="background: #fff; border-radius: 28px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; background: #f5f3ff; color: #8b5cf6; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <?php 
                                if(strpos($res['file_type'], 'pdf') !== false) echo '<i class="fas fa-file-pdf"></i>';
                                elseif(strpos($res['file_type'], 'image') !== false) echo '<i class="fas fa-file-image"></i>';
                                elseif(strpos($res['file_type'], 'presentation') !== false || strpos($res['file_type'], 'powerpoint') !== false) echo '<i class="fas fa-file-powerpoint"></i>';
                                else echo '<i class="fas fa-file-alt"></i>';
                            ?>
                        </div>
                        <span style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;"><?php echo date('M d, Y', strtotime($res['created_at'])); ?></span>
                    </div>

                    <h3 style="font-size: 1.15rem; color: #1e293b; font-weight: 800; margin-bottom: 8px;"><?php echo htmlspecialchars($res['title']); ?></h3>
                    <p style="font-size: 0.85rem; color: #8b5cf6; font-weight: 700; margin-bottom: 12px;"><?php echo htmlspecialchars($res['course_name'] . ' (' . $res['course_code'] . ')'); ?></p>
                    
                    <p style="font-size: 0.9rem; color: #64748b; line-height: 1.6; margin-bottom: 25px; height: 45px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        <?php echo htmlspecialchars($res['description'] ?: 'No description provided.'); ?>
                    </p>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #f8fafc;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 24px; height: 24px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #64748b;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 700;">Prof. <?php echo htmlspecialchars($res['first_name']); ?></span>
                        </div>
                        <a href="download_resource.php?id=<?php echo $res['resource_id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 800; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
document.getElementById('resourceSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    
    fetch(`api_search_resources.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('resourceGrid');
            const countSpan = document.getElementById('resourceCount');
            grid.innerHTML = '';
            countSpan.innerHTML = `<i class="fas fa-book-open" style="margin-right: 8px;"></i>${data.length} Resources Found`;
            
            if (data.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; background: #fff; padding: 80px; border-radius: 35px; text-align: center; border: 2px dashed #e2e8f0; width: 100%;">
                        <i class="fas fa-folder-open" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                        <h3 style="color: #64748b; font-weight: 800; font-size: 1.4rem;">No matching resources</h3>
                        <p style="color: #94a3b8; font-weight: 600;">Try adjusting your search filters.</p>
                    </div>
                `;
                return;
            }
            
            data.forEach(res => {
                const card = document.createElement('div');
                card.className = 'resource-card';
                card.style.cssText = 'background: #fff; border-radius: 28px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; transition: transform 0.3s ease, box-shadow 0.3s ease;';
                
                let icon = '<i class="fas fa-file-alt"></i>';
                if(res.file_type.includes('pdf')) icon = '<i class="fas fa-file-pdf"></i>';
                else if(res.file_type.includes('image')) icon = '<i class="fas fa-file-image"></i>';
                else if(res.file_type.includes('presentation') || res.file_type.includes('powerpoint')) icon = '<i class="fas fa-file-powerpoint"></i>';

                const dateStr = new Date(res.created_at).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; background: #f5f3ff; color: #8b5cf6; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            ${icon}
                        </div>
                        <span style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">${dateStr}</span>
                    </div>
                    <h3 style="font-size: 1.15rem; color: #1e293b; font-weight: 800; margin-bottom: 8px;">${res.title}</h3>
                    <p style="font-size: 0.85rem; color: #8b5cf6; font-weight: 700; margin-bottom: 12px;">${res.course_name} (${res.course_code})</p>
                    <p style="font-size: 0.9rem; color: #64748b; line-height: 1.6; margin-bottom: 25px; height: 45px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        ${res.description || 'No description provided.'}
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #f8fafc;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 24px; height: 24px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #64748b;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 700;">Prof. ${res.first_name}</span>
                        </div>
                        <a href="download_resource.php?id=${res.resource_id}" style="color: #3b82f6; text-decoration: none; font-weight: 800; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                `;
                grid.appendChild(card);
            });
        })
        .catch(error => console.error('Error:', error));
});
</script>

<style>
.resource-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.05) !important;
}
</script>

<?php include_once '../includes/footer.php'; ?>


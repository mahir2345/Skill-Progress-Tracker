<?php
$pageTitle = 'Goals - Smart Skill Progress Tracker';
$currentPage = 'goals';
$pageScript = 'goals.js';
include __DIR__ . '/../layouts/header.php';

// Goals data is now provided by the routing in index.php
?>

<main style="max-width: 1200px; margin: 20px auto; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="color: #333; margin-bottom: 5px;">My Goals</h2>
            <p style="color: #666; margin: 0;">Track your learning objectives and achievements</p>
        </div>
        <a href="<?php echo BASE_URL; ?>?page=goal-create" class="btn btn-primary" style="padding: 10px 20px; text-decoration: none;">
            <i class="fas fa-plus"></i> Set New Goal
        </a>
    </div>

    <?php if (hasFlashMessage()): ?>
        <div style="padding: 10px; margin-bottom: 20px; border-radius: 4px; <?php echo getFlashMessage()['type'] === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
            <?php echo getFlashMessage()['message']; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($goals)): ?>
        <div style="display: grid; gap: 20px;">
            <?php foreach ($goals as $goal): ?>
                <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $goal['status'] === 'completed' ? '#28a745' : ($goal['status'] === 'overdue' ? '#dc3545' : '#007bff'); ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <h4 style="color: #333; margin-bottom: 5px;"><?php echo htmlspecialchars($goal['skill_name']); ?></h4>
                            <p style="color: #666; margin-bottom: 10px;">
                                Target: <strong><?php echo $goal['target_proficiency']; ?></strong> | 
                                Current: <strong><?php echo $goal['current_proficiency']; ?></strong>
                            </p>
                            <?php if (!empty($goal['description'])): ?>
                                <p style="color: #555; margin-bottom: 10px;"><?php echo htmlspecialchars($goal['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <?php if ($goal['status'] === 'completed'): ?>
                                <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Completed</span>
                            <?php elseif ($goal['status'] === 'overdue'): ?>
                                <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Overdue</span>
                            <?php else: ?>
                                <span style="background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee;">
                        <div style="color: #666; font-size: 14px;">
                            <i class="fas fa-calendar"></i> 
                            Target Date: <?php echo date('M j, Y', strtotime($goal['target_date'])); ?>
                            <?php if ($goal['status'] !== 'completed'): ?>
                                <?php 
                                $daysLeft = ceil((strtotime($goal['target_date']) - time()) / (60 * 60 * 24));
                                if ($daysLeft > 0): 
                                ?>
                                    <span style="color: #28a745;">(<?php echo $daysLeft; ?> days left)</span>
                                <?php elseif ($daysLeft === 0): ?>
                                    <span style="color: #ffc107;">(Due today)</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">(<?php echo abs($daysLeft); ?> days overdue)</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <small style="color: #999;">Created: <?php echo date('M j, Y', strtotime($goal['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <div style="text-align: center; margin-top: 30px;">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a href="<?php echo BASE_URL; ?>?page=goals&page=<?php echo $i; ?>" 
                       style="display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border-radius: 4px; <?php echo $i === $pagination['current_page'] ? 'background: #007bff; color: white;' : 'background: #f8f9fa; color: #007bff;'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <i class="fas fa-target" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
            <h4 style="color: #666; margin-bottom: 10px;">No Goals Set Yet</h4>
            <p style="color: #999; margin-bottom: 20px;">Start setting goals to track your learning progress and stay motivated!</p>
            <a href="<?php echo BASE_URL; ?>?page=goal-create" class="btn btn-primary" style="padding: 12px 24px; text-decoration: none;">
                <i class="fas fa-plus"></i> Set Your First Goal
            </a>
        </div>
    <?php endif; ?>
</main>

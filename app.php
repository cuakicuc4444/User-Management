<?php
session_start();
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /sign_in');
    exit();
}
$apiUrl = 'http://localhost:8080/app/get';
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Location: http://localhost/sign_in');
    exit();
}
if (!isset($_SESSION['user_email'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Location: http://localhost/sign_in');
    exit();
}

$apps = json_decode($response, true);
if (!is_array($apps)) {
    $apps = [];
} elseif (isset($apps[0]) && !is_array($apps[0]) && is_array($apps)) {
    $apps = [$apps];
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchError = '';
$perPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;

if ($search !== '') {
    $searchLower = mb_strtolower($search, 'UTF-8');
    $filteredApps = array_filter($apps, function ($app) use ($searchLower) {
        $name = isset($app['name']) ? mb_strtolower($app['name'], 'UTF-8') : '';
        $altName = isset($app['alt_name']) ? mb_strtolower($app['alt_name'], 'UTF-8') : '';
        $developer = isset($app['developer']) ? mb_strtolower($app['developer'], 'UTF-8') : '';
        return strpos($name, $searchLower) !== false || strpos($altName, $searchLower) !== false || strpos($developer, $searchLower) !== false;
    });
    $filteredApps = array_values($filteredApps);
    if (count($filteredApps) === 0) {
        $searchError = 'No matching app found: ' . htmlspecialchars($search);
    } else {
        $apps = $filteredApps;
    }
}
$total = count($apps);
$totalPages = max(1, ceil($total / $perPage));
if ($page > $totalPages)
    $page = $totalPages;
$start = ($page - 1) * $perPage;
$appsPage = array_slice($apps, $start, $perPage);

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>App Management</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/user/style4.css">
</head>

<body>
    <div class="layout-flex">
        <div id="sidebar" class="sidebar expanded">
            <div class="sidebar-logo"
                style="display:flex;align-items:center;justify-content:center;height:35px;padding:12px 0;">
                <img src="/user/logo.png" alt="Logo" style="height:40px;width:auto;max-width:80%;object-fit:contain;"
                    onerror="this.style.display='none'">
            </div>
            <div class="sidebar-divider" style="height:0;border-bottom:1px solid #e0e0e0;opacity:0.7;margin:0 0 0 1px;">
            </div>
            <?php
            $currentPage = basename($_SERVER['PHP_SELF']);
            ?>
            <ul style="list-style:none;padding:0;margin:0;">
                <li
                    class="sidebar-menu-item<?php echo ($currentPage === 'index.php' || $currentPage === 'user.php') ? ' active' : ''; ?>">
                    <a href="/user"
                        class="<?php echo ($currentPage === 'index.php' || $currentPage === 'user.php') ? 'active' : ''; ?>"
                        style="display:flex;align-items:center;padding:12px 24px 12px 28px;color:inherit;text-decoration:none;font-size:17px;font-weight:500;gap:12px;">
                        <i class="ri-user-3-line" style="font-size:18px;"></i>
                        <span class="sidebar-label">User</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_rule']) && $_SESSION['user_rule'] === 'admin'): ?>
                    <li class="sidebar-menu-item<?php echo ($currentPage === 'account.php') ? ' active' : ''; ?>">
                        <a href="/account" class="<?php echo ($currentPage === 'account.php') ? 'active' : ''; ?>"
                            style="display:flex;align-items:center;padding:12px 24px 12px 28px;color:inherit;text-decoration:none;font-size:17px;font-weight:500;gap:12px;">
                            <i class="ri-user-settings-line" style="font-size:18px;"></i>
                            <span class="sidebar-label">Account</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item<?php echo ($currentPage === 'app.php') ? ' active' : ''; ?>">
                        <a href="/app" class="<?php echo ($currentPage === 'app.php') ? 'active' : ''; ?>"
                            style="display:flex;align-items:center;padding:12px 24px 12px 28px;color:inherit;text-decoration:none;font-size:17px;font-weight:500;gap:12px;">
                            <i class="ri-user-3-line" style="font-size:18px;"></i>
                            <span class="sidebar-label">App</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="main-content" id="mainContent">
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const sidebar = document.getElementById('sidebar');
                    const menuBtn = document.getElementById('menuBtn');
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('expanded');
                    document.querySelectorAll('.sidebar-label').forEach(function (label) {
                        label.style.display = '';
                    });
                    menuBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        if (sidebar.classList.contains('collapsed')) {
                            sidebar.classList.remove('collapsed');
                            sidebar.classList.add('expanded');
                            document.querySelectorAll('.sidebar-label').forEach(function (label) {
                                label.style.display = '';
                            });
                        } else {
                            sidebar.classList.remove('expanded');
                            sidebar.classList.add('collapsed');
                            document.querySelectorAll('.sidebar-label').forEach(function (label) {
                                label.style.display = 'none';
                            });
                        }
                    });
                    const tableMenuBtn = document.getElementById('tableMenuBtn');
                    const tableSubMenu = document.getElementById('tableSubMenu');
                    const tableMenuArrow = document.getElementById('tableMenuArrow');
                    let tableMenuOpen = false;
                    if (tableMenuBtn && tableSubMenu && tableMenuArrow) {
                        tableMenuBtn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            tableMenuOpen = !tableMenuOpen;
                            tableSubMenu.style.display = tableMenuOpen ? 'block' : 'none';
                            tableMenuArrow.style.transform = tableMenuOpen ? 'rotate(180deg)' : 'rotate(0)';
                        });
                    }
                    // Avatar dropdown logic
                    var avatarBtn = document.getElementById('avatarDropdownBtn');
                    var dropdown = document.getElementById('avatarDropdownMenu');
                    if (avatarBtn && dropdown) {
                        avatarBtn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                        });
                        document.addEventListener('click', function (e) {
                            if (!dropdown.contains(e.target) && e.target !== avatarBtn) {
                                dropdown.style.display = 'none';
                            }
                        });
                    }
                });
            </script>
            <div class="topbar"
                style="width:100%;background:#fff;border-bottom:2px solid #e0e0e0;display:flex;align-items:center;justify-content:space-between;padding:10px 32px 10px 18px;box-sizing:border-box;gap:18px;">
                <!-- Menu icon (hamburger) -->
                <div style="display:flex;align-items:center;gap:10px;">
                    <button id="menuBtn"
                        style="background:none;border:none;outline:none;cursor:pointer;padding:0 8px 0 0;display:flex;align-items:center;">
                        <i class="ri-menu-line" style="font-size:28px;color:#38487c;"></i>
                    </button>
                    <form method="get" class="topbar-search-form"
                        style="position:relative;display:flex;align-items:center;margin-left:12px;">
                        <input type="text" name="search" placeholder="Search for Results..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="border:none;outline:none;padding:10px 38px 10px 16px;border-radius:24px;font-size:15px;background:#fafbff;color:#38487c;box-shadow:0 0 0 1.5px #e0e7ef;min-width:220px;transition:box-shadow 0.2s;">
                        <button type="submit"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;outline:none;cursor:pointer;padding:0;">
                            <i class="ri-search-line" style="font-size:20px;color:#bfc8e2;"></i>
                        </button>
                    </form>
                </div>
                <!-- Avatar group -->
                <div style="display:flex;align-items:center;gap:16px;">
                    <div class="avatar-dropdown" style="position:relative;">
                        <span id="avatarDropdownBtn" class="avatar avatar-xs avatar-rounded"
                            style="background:#e6f4ff;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;color:#7b61ff;cursor:pointer;border:2px solid #8b7eff;">
                            <?php echo isset($_SESSION['user_name']) ? mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'), 'UTF-8') : 'U'; ?>
                        </span>
                        <div id="avatarDropdownMenu"
                            style="display:none;position:absolute;right:0;top:44px;min-width:260px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.12);padding:20px 0 10px 0;z-index:100;">
                            <div style="padding:0 24px 12px 24px;border-bottom:1px solid #f0f0f0;">
                                <div style="font-weight:700;font-size:18px;line-height:1.2;">
                                    <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                                </div>
                                <div style="font-size:14px;color:#888;">
                                    <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>
                                </div>
                            </div>
                            <a href="#"
                                style="display:flex;align-items:center;gap:12px;padding:14px 24px 8px 24px;color:#222;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-user-line"></i></span> My Profile
                            </a>
                            <a href="#"
                                style="display:flex;align-items:center;gap:12px;padding:8px 24px;color:#222;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-mail-line"></i></span> Mail Inbox
                            </a>
                            <a href="/account_setting"
                                style="display:flex;align-items:center;gap:12px;padding:8px 24px;color:#222;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-settings-3-line"></i></span> Account Settings
                            </a>
                            <a href="?logout=1"
                                style="display:flex;align-items:center;gap:12px;padding:8px 24px 14px 24px;color:#e44a8b;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-logout-box-r-line"></i></span> Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main-center">
                <h2>App Management</h2>
                <?php if (!empty($searchError)): ?>
                    <div class="search-error" style="margin-bottom:10px;"> <?php echo $searchError; ?> </div>
                <?php endif; ?>
                <div class="table-container">
                    <table class="app-table">
                        <colgroup>
                            <col style="width: 40px;">
                            <col style="width: 60px;">
                            <col style="width: 180px;">
                            <col style="width: 100px;">
                            <col style="width: 100px;">
                            <col style="width: 100px;">
                            <col style="width: 140px;">
                            <col style="width: 100px;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Version</th>
                                <th>Developer</th>
                                <th>Size (MB)</th>
                                <th>Link download</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $hasData = false;
                            foreach ($appsPage as $row):
                                // Kiểm tra có dữ liệu thực sự không (id hoặc name phải có)
                                if (empty($row['id']) && empty($row['name']))
                                    continue;
                                $hasData = true;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td>
                                        <?php
                                        // Hiển thị icon: nếu là mảng thì lấy phần tử đầu tiên
                                        $icon = '';
                                        if (!empty($row['icon'])) {
                                            if (is_array($row['icon'])) {
                                                $icon = $row['icon'][0] ?? '';
                                            } else {
                                                // Nếu là chuỗi JSON
                                                $decoded = json_decode($row['icon'], true);
                                                if (is_array($decoded)) {
                                                    $icon = $decoded[0] ?? '';
                                                } else {
                                                    $icon = $row['icon'];
                                                }
                                            }
                                        }
                                        if ($icon) {
                                            echo '<img src="' . htmlspecialchars($icon) . '" alt="icon" style="height:40p     x;max-width:40px;object-fit:contain;">';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo isset($row['name']) ? htmlspecialchars($row['name']) : ''; ?></td>
                                    <td><?php echo isset($row['version']) ? htmlspecialchars($row['version']) : ''; ?></td>
                                    <td><?php echo isset($row['developer']) ? htmlspecialchars($row['developer']) : ''; ?>
                                    </td>
                                    <td><?php echo isset($row['size_mb']) ? htmlspecialchars($row['size_mb']) : ''; ?></td>
                                    <td>
                                        <?php if (!empty($row['download_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['download_link']); ?>"
                                                class="download-btn">Download</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $editOnClick = "openAppEditModal('" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "');return false;";
                                        echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="Edit" style="display:inline-block;vertical-align:middle;margin-right:6px;text-decoration:none;color:#8b7eff;font-size:22px;" onclick="' . $editOnClick . '"><i class="ri-edit-line"></i></a>';
                                        $delUrl = '?delete=' . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
                                        echo '<a href="#" class="text-danger fs-14 lh-1 tooltip-hover" data-tooltip="Delete" style="display:inline-block;vertical-align:middle;text-decoration:none;color:#f44;font-size:22px;" onclick="showAppDeleteConfirm(\'' . htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8') . '\');return false;"><i class="ri-delete-bin-5-line"></i></a>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$hasData): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;">No data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 16px; width: 100%; max-width: 950px; margin: -1px auto 0 auto;">

                </div>
            </div>
        </div>
        <!-- Modal chỉnh sửa app -->
        <div id="appEditModal" class="modal"
            style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);align-items:center;justify-content:center;">
            <div class="user-modal-content app-modal-truegrid">
                <h3 id="appModalTitle" class="app-modal-title">Edit App</h3>
                <form id="appForm" method="post" autocomplete="off" onsubmit="return false;">
                    <input type="hidden" name="id" id="modal_app_id">
                    <div class="app-modal-rowflex">
                        <div class="app-modal-iconblock-true">
                            <div id="modal_app_icon_preview" class="app-modal-icon-preview-true"></div>
                        </div>
                        <div class="app-modal-fields-true">
                            <div class="app-modal-fieldline"><label for="modal_app_name">Name:</label><input type="text"
                                    name="name" id="modal_app_name" class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_version">Version:</label><input
                                    type="text" name="version" id="modal_app_version" class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_developer">Developer:</label><input
                                    type="text" name="developer" id="modal_app_developer" class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_category">Category:</label><input
                                    type="text" name="category" id="modal_app_category" class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_size_mb">Size (MB):</label><input
                                    type="text" name="size_mb" id="modal_app_size_mb" class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_google_play_id">Google Play
                                    ID:</label><input type="text" name="google_play_id" id="modal_app_google_play_id"
                                    class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_download_link">Download
                                    Link:</label><input type="text" name="download_link" id="modal_app_download_link"
                                    class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_updated_date">Updated
                                    Date:</label><input type="text" name="updated_date" id="modal_app_updated_date"
                                    class="input-user"></div>
                            <div class="app-modal-fieldline"><label for="modal_app_installs">Installs:</label><input
                                    type="text" name="installs" id="modal_app_installs" class="input-user"></div>
                        </div>
                    </div>
                    <div class="app-modal-descblock-true">
                        <label for="modal_app_description">Description:</label>
                        <textarea name="description" id="modal_app_description"
                            class="input-user app-modal-descarea-true"
                            style="font-family: 'Segoe UI', Poppins, sans-serif;"></textarea>
                    </div>
                    <div class="app-modal-screenshotsblock-true">
                        <label>Screenshots:</label>
                        <div id="modal_app_screenshots_preview" class="app-modal-screenshots-list-true"></div>
                    </div>
                    <div id="modalAppError" style="color:#b00020;text-align:center;margin-bottom:8px;display:none;">
                    </div>
                    <div class="app-modal-btnrow">
                        <button type="submit" class="search-btn" id="modalAppSaveBtn">Save</button>
                        <button type="button" class="search-btn" style="background:#aaa;"
                            onclick="closeAppEditModal()">Cancel</button>
                    </div>
                </form>
                <button onclick="closeAppEditModal()" class="app-modal-closebtn">&times;</button>
            </div>
        </div>

        <script>
            // Lưu dữ liệu app vào biến JS để dùng cho modal
            var appsData = <?php echo json_encode($appsPage, JSON_UNESCAPED_UNICODE); ?>;
            function openAppEditModal(id) {
                var modal = document.getElementById('appEditModal');
                var app = null;
                for (var i = 0; i < appsData.length; i++) {
                    if (appsData[i].id == id) { app = appsData[i]; break; }
                }
                if (!app) return;
                document.getElementById('modal_app_id').value = app.id || '';
                document.getElementById('modal_app_name').value = app.name || '';
                document.getElementById('modal_app_version').value = app.version || '';
                document.getElementById('modal_app_developer').value = app.developer || '';
                document.getElementById('modal_app_category').value = app.category || '';
                document.getElementById('modal_app_size_mb').value = app.size_mb || '';
                document.getElementById('modal_app_download_link').value = app.download_link || '';
                // Xử lý icon: chỉ lấy link đầu tiên, input chỉ hiện link, preview hiện ảnh
                var icon = app.icon;
                var iconLink = '';
                if (Array.isArray(icon)) iconLink = icon[0] || '';
                else if (typeof icon === 'string' && icon.startsWith('[')) {
                    try { var arr = JSON.parse(icon); iconLink = arr[0] || ''; } catch (e) { iconLink = ''; }
                } else if (typeof icon === 'string') iconLink = icon;
                // Không còn input icon, chỉ preview ảnh
                var iconPreview = document.getElementById('modal_app_icon_preview');
                iconPreview.innerHTML = '';
                if (iconLink) {
                    var img = document.createElement('img');
                    img.src = iconLink;
                    img.className = 'screenshot-thumb';
                    img.alt = 'icon';
                    iconPreview.appendChild(img);
                }
                document.getElementById('modal_app_updated_date').value = app.updated_date || '';
                document.getElementById('modal_app_google_play_id').value = app.google_play_id || '';
                document.getElementById('modal_app_installs').value = app.installs || '';
                document.getElementById('modal_app_description').value = app.description || '';
                // Xử lý screenshots: input chỉ hiện link (dạng mảng hoặc chuỗi, nếu là JSON thì chuyển sang link, nếu là mảng thì join, nếu là chuỗi thì giữ nguyên), preview hiện ảnh
                var screenshots = app.screenshots;
                var arr = [];
                if (Array.isArray(screenshots)) arr = screenshots;
                else if (typeof screenshots === 'string' && screenshots.startsWith('[')) {
                    try { arr = JSON.parse(screenshots); if (!Array.isArray(arr)) arr = [screenshots]; } catch { arr = []; }
                } else if (typeof screenshots === 'string') arr = screenshots.split(',').map(s => s.trim()).filter(Boolean);
                // input chỉ hiện link, không hiện JSON
                // Không còn input screenshots, chỉ preview ảnh
                // Hiển thị preview ảnh screenshots
                var previewDiv = document.getElementById('modal_app_screenshots_preview');
                previewDiv.innerHTML = '';
                // Gán sự kiện click để xem ảnh lớn
                window._modalScreenshotsArr = arr;
                arr.forEach(function (link, idx) {
                    if (link) {
                        var img = document.createElement('img');
                        img.src = link;
                        img.className = 'screenshot-thumb';
                        img.alt = 'screenshot';
                        img.style.cursor = 'pointer';
                        img.onclick = function () { openScreenshotViewer(idx); };
                        previewDiv.appendChild(img);
                    }
                });

                if (window.closeScreenshotViewer) window.closeScreenshotViewer();
                window.openScreenshotViewer = function (idx) {
                    var arr = window._modalScreenshotsArr || [];
                    if (!arr.length) return;
                    window._currentScreenshotIdx = idx;
                    var modal = document.getElementById('screenshotViewerModal');
                    var img = document.getElementById('screenshotViewerImg');
                    img.src = arr[idx];
                    modal.style.display = 'flex';
                }
                window.closeScreenshotViewer = function () {
                    var modal = document.getElementById('screenshotViewerModal');
                    if (modal) modal.style.display = 'none';
                }
                window.prevScreenshot = function (e) {
                    e.stopPropagation();
                    var arr = window._modalScreenshotsArr || [];
                    if (!arr.length) return;
                    window._currentScreenshotIdx = (window._currentScreenshotIdx - 1 + arr.length) % arr.length;
                    document.getElementById('screenshotViewerImg').src = arr[window._currentScreenshotIdx];
                }
                window.nextScreenshot = function (e) {
                    e.stopPropagation();
                    var arr = window._modalScreenshotsArr || [];
                    if (!arr.length) return;
                    window._currentScreenshotIdx = (window._currentScreenshotIdx + 1) % arr.length;
                    document.getElementById('screenshotViewerImg').src = arr[window._currentScreenshotIdx];
                }
                document.getElementById('modalAppError').style.display = 'none';
                document.getElementById('appEditModal').style.display = 'flex';
            }
            function closeAppEditModal() {
                document.getElementById('appEditModal').style.display = 'none';
            }
        </script>
    </div>
    <!-- Modal xem ảnh lớn đặt ngoài appEditModal để overlay toàn màn hình -->
    <div id="screenshotViewerModal" class="screenshot-viewer-modal" style="display:none;">
        <div class="screenshot-viewer-overlay" onclick="closeScreenshotViewer()"></div>
        <div class="screenshot-viewer-content">
            <button class="screenshot-viewer-close" onclick="closeScreenshotViewer()">&times;</button>
            <button class="screenshot-viewer-prev" onclick="prevScreenshot(event)">&#10094;</button>
            <img id="screenshotViewerImg" src="" alt="screenshot" />
            <button class="screenshot-viewer-next" onclick="nextScreenshot(event)">&#10095;</button>
        </div>
    </div>
</body>

</html>
<?php
session_start();
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /sign_in.php');
    exit();
} ?>
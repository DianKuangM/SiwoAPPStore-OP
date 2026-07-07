</html>
<?php
require 'config.php';
$user = get_user();

// 获取搜索关键词
$keyword = isset($_GET['kw']) ? trim($_GET['kw']) : '';
// 获取当前选中分类
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
// 当前页码
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$pageSize = 10; // 每页10条，可自行修改

// 固定9个分类，和截图完全一致
$fixedCats = [
    "拾物系列","游戏","工具","社交","娱乐",
    "摄影","教育","购物","新闻"
];

// 生成带筛选参数的分页链接
function buildPageUrl($p){
    global $keyword,$cat;
    $query = [];
    if($keyword) $query['kw'] = $keyword;
    if($cat) $query['cat'] = $cat;
    $query['p'] = $p;
    return 'index.php?'.http_build_query($query);
}

// 统计总数据量
$countSql = "SELECT COUNT(*) total FROM apps WHERE is_shelved=1";
$paramsCount = [];
if (!empty($keyword)) {
    $countSql .= " AND (app_name LIKE ? OR package_name LIKE ? OR description LIKE ?)";
    $paramsCount[] = "%$keyword%";
    $paramsCount[] = "%$keyword%";
    $paramsCount[] = "%$keyword%";
}
if (!empty($cat)) {
    $countSql .= " AND category=?";
    $paramsCount[] = $cat;
}
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($paramsCount);
$totalRow = (int)$stmtCount->fetch()['total'];
$totalPage = ceil($totalRow / $pageSize);

// 页码边界限制
if ($page < 1) $page = 1;
if ($totalPage > 0 && $page > $totalPage) $page = $totalPage;
$offset = ($page - 1) * $pageSize;

// 分页数据查询
$sql = "SELECT * FROM apps WHERE is_shelved=1";
$params = [];

// 关键词模糊搜索：应用名、包名、简介
if (!empty($keyword)) {
    $sql .= " AND (app_name LIKE ? OR package_name LIKE ? OR description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

// 分类精准筛选
if (!empty($cat)) {
    $sql .= " AND category=?";
    $params[] = $cat;
}

$sql .= " ORDER BY is_top DESC, create_time DESC LIMIT ?,?";
// 先把两个数值存入数组
$params[] = $offset;
$params[] = $pageSize;

$stmt = $pdo->prepare($sql);

// 1.循环绑定前面所有普通参数（模糊搜索、分类）
$totalParams = count($params);
// LIMIT是最后两个占位符下标
$limitOffsetPos = $totalParams - 1;
$limitSizePos = $totalParams;

for ($i = 0; $i < $totalParams; $i++) {
    // 跳过最后两个，后面单独绑定为INT
    if ($i == $limitOffsetPos || $i == $limitSizePos - 1) {
        continue;
    }
    $stmt->bindValue($i + 1, $params[$i]);
}

// 2.单独把LIMIT两个参数强制绑定为整型，解决带引号语法错误
$stmt->bindValue($limitOffsetPos, $offset, PDO::PARAM_INT);
$stmt->bindValue($limitSizePos, $pageSize, PDO::PARAM_INT);

// 3.执行，不再传数组进去
$stmt->execute();
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>拾物 Siwo - 轻量资源应用商店</title>
<?php include 'style.html'; ?>
<style>
.app-row-card{
    display:flex;
    align-items:center;
    gap:16px;
    padding:16px;
    border-radius:12px;
    background:var(--card);
    box-shadow:0 1px 4px rgba(0,0,0,0.08);
    margin-bottom:12px;
}
.app-row-icon{
    width:60px;
    height:60px;
    border-radius:10px;
    object-fit:cover;
    flex-shrink:0;
}
.app-row-info{
    flex:1;
}
.app-row-name{
    font-size:18px;
    font-weight:bold;
    margin-bottom:4px;
    display:flex;
    align-items:center;
    gap:8px;
}
.app-row-ver{
    color:var(--gray);
    font-size:14px;
}
.search-bar{
    display:flex;
    gap:8px;
    margin:20px 0;
}
.search-bar input{
    flex:1;
    margin:0;
}
.category-nav{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin:15px 0 25px;
}
.cat-item{
    padding:6px 14px;
    border-radius:20px;
    border:1px solid #ccc;
    text-decoration:none;
    font-size:14px;
    color:var(--text);
}
.cat-active{
    background:var(--main);
    color:#fff;
    border-color:var(--main);
}
.top-tag{
    background:#ff4d4f;
    color:#fff;
    font-size:12px;
    padding:3px 8px;
    border-radius:4px;
}
.carousel-container {
    width: 100%;
    max-width: 720px;
    margin: 12px auto;
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    background: #f5f7fb;
}

.carousel-wrapper {
    display: flex;
    transition: transform 0.5s ease;
}

.carousel-item {
    min-width: 100%;
    height: 180px;
}

.carousel-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.carousel-dots {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
}

.carousel-dots .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
}

.carousel-dots .dot.active {
    background: #ffffff;
}
/* 汉堡菜单基础 */
.hamburger {
    display: none;
    font-size: 28px;
    color: #fff;
    cursor: pointer;
}
.mobile-menu {
    display: none;
    position: absolute;
    top: 60px;
    right: 15px;
    background: var(--main);
    border-radius: 12px;
    padding: 15px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    z-index:999;
}
.mobile-menu a {
    display:block;
    color:#fff;
    padding:9px 4px;
    text-decoration:none;
    font-size:16px;
}
.mobile-menu.show {
    display:block;
}

/* 768px以下手机端切换 */
@media (max-width:768px){
    .nav-desktop {
        display:none !important;
    }
    .hamburger {
        display:block;
    }
}
/* 分页样式 */
.pagination-box{
    margin:35px 0;
    text-align:center;
}
.pagination-box a.btn{
    margin:0 5px;
}
.pagination-box .current-page{
    background:#333;
    color:#fff;
}
.page-tip{
    margin-top:12px;
    color:#666;
    font-size:14px;
}
</style>
</head>
<body>
<header>
    <div class="container head-row">
        <a href="index.php" class="logo">拾物 Siwo</a>

        <!-- 桌面端正常导航 -->
        <div class="nav nav-desktop">
            <a href="index.php">首页</a>
            <?php if($user): ?>
                <a href="upload.php">上传应用</a>
                <a href="sign.php">每日签到</a>
                <a href="logout.php">退出(<?=htmlspecialchars($user['username'])?>)</a>
                <a href="friends.php">友情链接</a>
                <a href="https://about.oldchat.shop">关于</a>
            <?php else: ?>
                <a href="login/">登录</a>
                <a href="register.php">注册</a>
                <a href="friends.php">友情链接</a>
                <a href="https://about.oldchat.shop">关于</a>
                <?php endif; ?>
        </div>

        <!-- 移动端汉堡按钮 -->
        <div class="hamburger" id="hamburger">☰</div>

        <!-- 移动端下拉菜单 -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="index.php">首页</a>
            <?php if($user): ?>
                <a href="upload.php">上传应用</a>
                <a href="sign.php">每日签到</a>
                <a href="logout.php">退出(<?=htmlspecialchars($user['username'])?>)</a>
                <a href="friends.php">友情链接</a>
                <a href="https://about.oldchat.shop">关于</a>
                <p>---</p>
                <a href="https://www.siwobbs.top">交流论坛</a>
                <a href="tuig.html">推广中心</a>
            <?php else: ?>
                <a href="login/">登录</a>
                <a href="register.php">注册</a>
                <a href="friends.php">友情链接</a>
                <a href="https://about.oldchat.shop">关于</a>
            <?php endif; ?>
        </div>
    </div>
</header>


<div class="container">
    <!-- 搜索框：搜索自动重置到第1页 -->
    <form class="search-bar" method="get" action="index.php">
        <?php if(!empty($cat)): ?>
            <input type="hidden" name="cat" value="<?=htmlspecialchars($cat)?>">
        <?php endif; ?>
        <input type="hidden" name="p" value="1">
        <input type="text" name="kw" placeholder="搜索应用名称、包名、简介" value="<?=htmlspecialchars($keyword)?>">
        <button class="btn">搜索</button>
    </form>
    
    <div class="carousel-container">
    <div class="carousel-wrapper">
        <div class="carousel-item active">
            <a href="#">
                <img src="banner/banner1.png" alt="推荐应用 1">
            </a>
        </div>
        <div class="carousel-item">
            <a href="app.php?id=13">
                <img src="banner/banner2.png" alt="推荐应用 2">
            </a>
        </div>
        <div class="carousel-item">
            <a href="tuig.html">
                <img src="banner/banner3.png" alt="推荐应用 3">
            </a>
        </div>
    </div>

    <div class="carousel-dots">
        <span class="dot active" data-index="0"></span>
        <span class="dot" data-index="1"></span>
        <span class="dot" data-index="2"></span>
    </div>
</div>


    <!-- 修复后的分类导航：切换分类强制p=1，保留关键词 -->
    <div class="category-nav">
        <?php
        $allQuery = ['p' => 1];
        if (!empty($keyword)) $allQuery['kw'] = $keyword;
        $allUrl = 'index.php?' . http_build_query($allQuery);
        ?>
        <a href="<?= $allUrl ?>" class="cat-item <?=empty($cat)?'cat-active':''?>">全部</a>

        <?php foreach($fixedCats as $c): ?>
            <?php
            $catQuery = [
                'cat' => $c,
                'p' => 1
            ];
            if (!empty($keyword)) $catQuery['kw'] = $keyword;
            $catUrl = 'index.php?' . http_build_query($catQuery);
            ?>
            <a href="<?= $catUrl ?>"
               class="cat-item <?=$cat==$c?'cat-active':''?>">
                <?=htmlspecialchars($c)?>
            </a>
        <?php endforeach; ?>
    </div>

    <h2>
        <?php
        if(!empty($cat)) echo htmlspecialchars($cat)."分类";
        else echo "全部应用";
        if(!empty($keyword)) echo " · 关键词：".htmlspecialchars($keyword);
        ?>
    </h2>

    <?php if(empty($apps)): ?>
        <p>暂无匹配应用</p>
    <?php else: foreach($apps as $app): ?>
    <div class="app-row-card">
        <?php if($app['icon']): ?>
            <img class="app-row-icon" src="<?=htmlspecialchars($app['icon'])?>">
        <?php else: ?>
            <div class="app-row-icon" style="background:#eee;display:flex;align-items:center;justify-content:center;">Siwo</div>
        <?php endif; ?>
        <div class="app-row-info">
            <div class="app-row-name">
                <?=htmlspecialchars($app['app_name'])?>
                <?php if($app['is_top'] == 1): ?>
                    <span class="top-tag">置顶</span>
                <?php endif; ?>
            </div>
            <div class="app-row-ver">
                版本：<?=htmlspecialchars($app['version'])?>
                &nbsp;&nbsp;分类：<?=htmlspecialchars($app['category'])?>
            </div>
        </div>
        <a href="app.php?id=<?=$app['id']?>" class="btn">查看详情</a>
    </div>
    <?php endforeach;endif; ?>

    <!-- 分页区域 -->
    <?php if($totalPage > 1): ?>
    <div class="pagination-box">
        <?php if($page > 1): ?>
            <a href="<?=buildPageUrl($page-1)?>" class="btn">上一页</a>
        <?php endif; ?>

        <?php
        // 只展示当前页前后2页，避免页码过多
        $startPage = max(1, $page - 2);
        $endPage = min($totalPage, $page + 2);
        for($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a href="<?=buildPageUrl($i)?>" class="btn <?=$i==$page?'current-page':''?>">
                <?=$i?>
            </a>
        <?php endfor; ?>

        <?php if($page < $totalPage): ?>
            <a href="<?=buildPageUrl($page+1)?>" class="btn">下一页</a>
        <?php endif; ?>

        <div class="page-tip">
            总计 <?= $totalRow ?> 条应用，当前第 <?= $page ?> / <?= $totalPage ?> 页
        </div>
    </div>
    <?php endif; ?>
</div>

<footer>
© 2026-现在 拾物应用商店 版权所有<br>
<a href="https://icp.redcha.cn/beian/ICP-2026070368.html" title="茶ICP备2026070368号" target="_blank"><img src="https://img.shields.io/badge/%E8%8C%B6ICP%E5%A4%87-2026070368号-7474e1?style=flat&logo=data:image/png;base64,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" /></a>
<p>Powered by FreeCafe</p>
</footer>
</body>
<script>
const items = document.querySelectorAll('.carousel-item');
const dots = document.querySelectorAll('.dot');
let current = 0;

function showSlide(index) {
    items[current].classList.remove('active');
    dots[current].classList.remove('active');

    current = index;

    items[current].classList.add('active');
    dots[current].classList.add('active');

    const wrapper = document.querySelector('.carousel-wrapper');
    wrapper.style.transform = `translateX(-${current * 100}%)`;
}

function nextSlide() {
    let next = (current + 1) % items.length;
    showSlide(next);
}

setInterval(nextSlide, 4000);

dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        showSlide(index);
    });
});
// 汉堡菜单控制
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
hamburger.onclick = function(){
  mobileMenu.classList.toggle('show');
};
// 点击页面空白处关闭菜单
document.addEventListener('click',function(e){
  if(!hamburger.contains(e.target) && !mobileMenu.contains(e.target)){
    mobileMenu.classList.remove('show');
  }
});
(function() {
    var statsId = 'b24b4a635413c8da5cf258c0677fd633';
    var statsDomain = 'oldchat.shop';
    var statsUrl = 'http://freenom.me.uk/track.php';
    
    function trackStats() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', statsUrl + '?id=' + statsId + '&domain=' + encodeURIComponent(statsDomain) + '&t=' + Date.now());
        xhr.send();
    }
    
    if (document.readyState === 'complete') {
        trackStats();
    } else {
        window.addEventListener('load', trackStats);
    }
})();
</script>
</html>

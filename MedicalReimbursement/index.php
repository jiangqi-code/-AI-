<?php
/* ==========================================================
 * 医疗报销系统 · 疾病目录页
 * 设计关键词：中性色 / 大留白 / 微动效 / 玻璃拟态
 * ========================================================== */
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>疾病报销目录 · 医疗报销系统</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <!-- CDN -->
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root{
            --body-bg:#f7f9fb;
            --text-main:#1f2937;
            --text-second:#6b7280;
            --accent:#4f46e5;
            --accent-light:#e0e7ff;
            --radius:24px;
            --transition:.3s cubic-bezier(.4,0,.2,1);
        }
        html{scroll-behavior:smooth;}
        body{
            background:var(--body-bg) url("926077b6656c909c804c262e715c94a.png") no-repeat center/cover fixed;
            color:var(--text-main);
            font-family:-apple-system,BlinkMacSystemFont,"Helvetica Neue","PingFang SC","Microsoft YaHei",sans-serif;
            letter-spacing:.4px;
            line-height:1.6;
        }
        a{text-decoration:none;color:inherit;}

        /* ------ 顶部 ------ */
        header{
            background:rgba(255,255,255,.72);
            backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(0,0,0,.05);
            position:sticky;
            top:0;
            z-index:1020;
        }
        .logo{
            font-weight:700;
            font-size:1.1rem;
            color:var(--accent);
        }
        .nav-link{
            font-size:.9rem;
            color:var(--text-second);
            margin-left:1.5rem;
            transition:color var(--transition);
        }
        .nav-link:hover{color:var(--accent);}

        /* ------ 标题 ------ */
        .page-title{
            font-weight:600;
            font-size:2.25rem;
            margin:4rem 0 3rem;
        }
        .page-title small{
            font-size:1rem;
            color:var(--text-second);
            margin-left:.5rem;
        }

        /* ------ 卡片 ------ */
        .disease-card{
            border:none;
            border-radius:var(--radius);
            background:rgba(255,255,255,.82);
            backdrop-filter:blur(12px);
            box-shadow:0 8px 32px rgba(0,0,0,.05);
            transition:transform var(--transition),box-shadow var(--transition);
            height:100%;
            display:flex;
            flex-direction:column;
        }
        .disease-card:hover{
            transform:translateY(-6px);
            box-shadow:0 12px 40px rgba(0,0,0,.08);
        }
        .card-header{
            border-radius:var(--radius) var(--radius) 0 0 !important;
            padding:1.5rem 1.75rem 1.25rem;
            font-weight:600;
            font-size:1.05rem;
            display:flex;
            align-items:center;
            gap:.6rem;
            background:var(--accent-light);
            color:var(--accent);
            border-bottom:1px solid rgba(0,0,0,.06);
        }
        .card-body{
            flex:1;
            padding:1.75rem;
        }
        .card-body ul{
            padding-left:1.2rem;
            font-size:.93rem;
            color:var(--text-second);
        }
        .card-body li+li{margin-top:.4rem;}
        .badge-reimburse{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            background:var(--accent);
            color:#fff;
            padding:.4rem .9rem;
            border-radius:99px;
            font-size:.8rem;
            margin-top:auto;
        }

        /* ------ 底部信息 ------ */
        .info-box{
            border-radius:var(--radius);
            background:rgba(255,255,255,.82);
            backdrop-filter:blur(12px);
            border:1px solid rgba(0,0,0,.06);
            padding:2.5rem;
            margin:4rem 0 5rem;
        }
        .btn-primary{
            background:var(--accent);
            border-color:var(--accent);
            border-radius:12px;
            padding:.55rem 1.6rem;
            font-size:.95rem;
            transition:background var(--transition);
        }
        .btn-primary:hover{background:#4338ca;border-color:#4338ca;}
    </style>
</head>
<body>

<!-- ========== 顶部导航 ========== -->
<header>
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a class="logo" href="./"><i class="fa-solid fa-house-chimney-medical me-2"></i>医疗报销系统</a>
        <nav class="d-flex">
            <a class="nav-link" href="login.php">用户登录</a>
            <a class="nav-link" href="admin_login.php">管理员登录</a>
        </nav>
    </div>
</header>

<!-- ========== 主内容 ========== -->
<main class="container">
    <h1 class="page-title text-center">
        可报销疾病目录
        <small>共 4 大类 · 实时更新</small>
    </h1>

    <div class="row g-4">
        <!-- 肿瘤 -->
        <div class="col-md-6 col-lg-3">
            <div class="card disease-card">
                <div class="card-header">
                    <i class="fa-solid fa-shield-virus"></i>肿瘤类
                </div>
                <div class="card-body">
                    <ul>
                        <li>恶性肿瘤化疗</li>
                        <li>肿瘤靶向治疗</li>
                        <li>肿瘤放射治疗</li>
                    </ul>
                    <div class="badge-reimburse">
                        <i class="fa-solid fa-check-circle"></i>报销 70-90%
                    </div>
                </div>
            </div>
        </div>

        <!-- 手术 -->
        <div class="col-md-6 col-lg-3">
            <div class="card disease-card">
                <div class="card-header">
                    <i class="fa-solid fa-hand-holding-medical"></i>手术治疗
                </div>
                <div class="card-body">
                    <ul>
                        <li>常规外科手术</li>
                        <li>微创手术</li>
                        <li>器官移植手术</li>
                    </ul>
                    <div class="badge-reimburse">
                        <i class="fa-solid fa-check-circle"></i>报销 60-85%
                    </div>
                </div>
            </div>
        </div>

        <!-- 慢性病 -->
        <div class="col-md-6 col-lg-3">
            <div class="card disease-card">
                <div class="card-header">
                    <i class="fa-solid fa-pills"></i>慢性病
                </div>
                <div class="card-body">
                    <ul>
                        <li>高血压Ⅱ期及以上</li>
                        <li>糖尿病</li>
                        <li>冠心病</li>
                    </ul>
                    <div class="badge-reimburse">
                        <i class="fa-solid fa-check-circle"></i>报销 50-75%
                    </div>
                </div>
            </div>
        </div>

        <!-- 急性病 -->
        <div class="col-md-6 col-lg-3">
            <div class="card disease-card">
                <div class="card-header">
                    <i class="fa-solid fa-heart-pulse"></i>急性病
                </div>
                <div class="card-body">
                    <ul>
                        <li>急性肺炎</li>
                        <li>急性阑尾炎</li>
                        <li>严重感染性疾病</li>
                    </ul>
                    <div class="badge-reimburse">
                        <i class="fa-solid fa-check-circle"></i>报销 65-80%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 说明 & CTA -->
    <div class="info-box">
        <h5 class="mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i>报销须知</h5>
        <ol class="small text-secondary mb-0">
            <li>所有疾病需提供正规医疗机构诊断证明；</li>
            <li>肿瘤类疾病须附病理报告；</li>
            <li>报销比例视治疗方式与总费用浮动；</li>
            <li>材料齐全后，3-5 个工作日完成审核。</li>
        </ol>
        <div class="mt-3">
            <a class="btn btn-primary" href="login.php">登录并申请报销</a>
        </div>
    </div>
</main>
</body>
</html>
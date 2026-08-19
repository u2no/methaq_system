<?php

$currentPage = basename($_SERVER['PHP_SELF']);

?>


<div
    class="d-flex flex-column flex-shrink-0 p-3 text-white shadow"
    style="
        width: 220px;
        min-height: 100vh;
        background-color: #1e293b !important;
    "
>


    <!-- =====================================================
         هيدر القائمة الجانبية
    ====================================================== -->

    <a
        href="index.php"
        class="d-flex flex-column align-items-start mb-3 text-white text-decoration-none border-bottom pb-2 w-100"
    >

        <div class="d-flex align-items-center mb-1">

            <i
                class="fa-solid fa-shield-halved fs-5 me-2 text-primary"
            ></i>

            <span class="fw-bold text-light fs-6">
                منصة ميثاق
            </span>

        </div>


        <span
            class="badge bg-primary text-white mt-1 opacity-75"
            style="font-size: 0.65rem;"
        >
            قسم الإمداد
        </span>

    </a>



    <!-- =====================================================
         القائمة الرئيسية
    ====================================================== -->

    <ul class="nav nav-pills flex-column mb-auto small">


        <!-- الرئيسية -->

        <li class="nav-item mb-1">

            <a
                href="index.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'index.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <i class="fa-solid fa-house me-2"></i>

                <span>
                    الرئيسية
                </span>

            </a>

        </li>



        <!-- إدارة المركبات -->

        <li class="mb-1">

            <a
                href="vehicles.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'vehicles.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <i class="fa-solid fa-car me-2"></i>

                <span>
                    إدارة المركبات
                </span>

            </a>

        </li>



        <!-- الموظفين -->

        <li class="mb-1">

            <a
                href="persons.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'persons.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <i class="fa-solid fa-user-gear me-2"></i>

                <span>
                    الموظفين
                </span>

            </a>

        </li>



        <!-- تسليم العهدة -->

        <li class="mb-1">

            <a
                href="assignment_add.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'assignment_add.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <i class="fa-solid fa-key me-2"></i>

                <span>
                    تسليم العهدة
                </span>

            </a>

        </li>



        <!-- استلام العهدة -->

        <li class="mb-1">

            <a
                href="assignment_return.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'assignment_return.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <i class="fa-solid fa-rotate-left me-2"></i>

                <span>
                    استلام العهدة
                </span>

            </a>

        </li>



        <!-- =====================================================
             العهد النشطة
        ====================================================== -->

        <li class="mb-1">

            <a
                href="assignment_active.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'assignment_active.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    fill="currentColor"
                    class="bi bi-clipboard-check-fill me-2"
                    viewBox="0 0 16 16"
                >

                    <path
                        d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5z"
                    />

                    <path
                        d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5zm6.854 7.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708"
                    />

                </svg>

                <span>
                    العهد النشطة
                </span>

            </a>

        </li>



        <!-- =====================================================
             العهد المستلمة
        ====================================================== -->

        <li class="mb-1">

            <a
                href="assignment_ended.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'assignment_ended.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    fill="currentColor"
                    class="bi bi-clipboard-x-fill me-2"
                    viewBox="0 0 16 16"
                >

                    <path
                        d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5z"
                    />

                    <path
                        d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5zm4 7.793 1.146-1.147a.5.5 0 1 1 .708.708L8.707 10l1.147 1.146a.5.5 0 0 1-.708.708L8 10.707l-1.146 1.147a.5.5 0 0 1-.708-.708L7.293 10 6.146 8.854a.5.5 0 1 1 .708-.708z"
                    />

                </svg>

                <span>
                    العهد المستلمة
                </span>

            </a>

        </li>



        <!-- التقارير والتنبيهات -->

        <li class="mb-1">

            <a
                href="reports.php"
                class="
                    nav-link
                    d-flex
                    align-items-center
                    py-2
                    px-2
                    rounded-2

                    <?php
                    echo $currentPage === 'reports.php'
                        ? 'active'
                        : 'text-light opacity-75';
                    ?>
                "
            >

                <i class="fa-solid fa-chart-pie me-2"></i>

                <span>
                    التقارير والتنبيهات
                </span>

            </a>

        </li>


    </ul>



    <!-- =====================================================
         الخط الفاصل
    ====================================================== -->

    <hr class="text-secondary my-2">



    <!-- =====================================================
         زر الخروج
    ====================================================== -->

    <div>

        <a
            href="logout.php"
            class="
                btn
                btn-sm
                btn-outline-danger
                w-100
                d-flex
                align-items-center
                justify-content-center
                gap-1
                rounded-2
            "
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                خروج
            </span>

        </a>

    </div>


</div>
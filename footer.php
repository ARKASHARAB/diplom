<?php
// footer.php - общий подвал сайта
?>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-door-closed"></i> <?= SITE_NAME ?></h5>
                    <p class="text-muted">Качественные межкомнатные двери от производителя. Широкий выбор моделей и цветов.</p>
                    <div class="social-links">
                        <a href="#" class="text-muted me-2"><i class="bi bi-telegram fs-5"></i></a>
                        <a href="#" class="text-muted me-2"><i class="bi bi-whatsapp fs-5"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-vk fs-5"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h6>Информация</h6>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-muted text-decoration-none">О компании</a></li>
                        <li><a href="delivery.php" class="text-muted text-decoration-none">Доставка и оплата</a></li>
                        <li><a href="guarantee.php" class="text-muted text-decoration-none">Гарантия</a></li>
                        <li><a href="contacts.php" class="text-muted text-decoration-none">Контакты</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6>Каталог</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php?category_name=Классика" class="text-muted text-decoration-none">Классические двери</a></li>
                        <li><a href="index.php?category_name=Современные" class="text-muted text-decoration-none">Современные двери</a></li>
                        <li><a href="index.php?category_name=Стеклянные" class="text-muted text-decoration-none">Стеклянные двери</a></li>
                        <li><a href="index.php?category_name=Экошпон" class="text-muted text-decoration-none">Двери экошпон</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6>Контакты</h6>
                    <ul class="list-unstyled">
                        <li class="text-muted"><i class="bi bi-telephone"></i> 8 (800) 123-45-67</li>
                        <li class="text-muted"><i class="bi bi-envelope"></i> info@dveri.ru</li>
                        <li class="text-muted"><i class="bi bi-geo-alt"></i> г. Москва, ул. Строителей, 15</li>
                    </ul>
                </div>
            </div>
            
            <hr class="bg-secondary">
            
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Все права защищены.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">Используется таблица: <?= TABLE_PRODUCTS ?></small><br>
                    <small class="text-muted">Товаров в базе: <?= $GLOBALS['total_items'] ?? '0' ?></small>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .footer {
            background: #3D3D3D;
            color: white;
            padding: 50px 0 30px;
            margin-top: 50px;
        }
        
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer h5, .footer h6 {
            color: white;
            margin-bottom: 20px;
        }
        
        .footer .text-muted {
            color: rgba(255,255,255,0.7) !important;
        }
        
        .footer a.text-muted:hover {
            color: white !important;
        }
        
        .footer hr {
            opacity: 0.2;
        }
        
        .social-links a {
            display: inline-block;
            transition: transform 0.3s;
        }
        
        .social-links a:hover {
            transform: translateY(-3px);
        }
        
        .main-container {
            min-height: calc(100vh - 200px);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Обновление счетчика корзины
    function updateCartCount() {
        fetch('get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.count;
                    cartCount.style.display = data.count > 0 ? 'inline-flex' : 'none';
                }
            });
    }
    
    // Автоматическое обновление счетчика каждые 30 секунд
    setInterval(updateCartCount, 30000);
    
    // Поиск при нажатии Enter
    document.querySelectorAll('.search-form input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    });
    </script>
</body>
</html>
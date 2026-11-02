    </main>
  </div>

  <script>
    (function() {
      const btn = document.getElementById('adminProfileBtn');
      const dropdown = document.getElementById('adminProfileDropdown');
      if (btn && dropdown) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
        document.addEventListener('click', function(e) {
          if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
          }
        });
      }
    })();
  </script>
</body>
</html>

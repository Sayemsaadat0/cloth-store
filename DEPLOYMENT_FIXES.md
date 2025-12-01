# Production Deployment Fixes

## Issues Fixed

### 1. CORS Configuration ✅
- **Problem**: POST/PUT/DELETE requests were failing with 500 errors in production due to missing CORS configuration
- **Solution**: Created `config/cors.php` with proper CORS settings
- **Action Required**: 
  - Set `CORS_ALLOWED_ORIGINS` in your production `.env` file:
    ```env
    CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com,https://www.your-frontend-domain.com
    ```
  - Or leave it as `*` for development (not recommended for production)

### 2. File Upload Permissions ✅
- **Problem**: File uploads were using `public_path()` directly, which may not be writable in production
- **Solution**: Updated `ProductController` to use Laravel's Storage facade
- **Action Required**:
  - Run `php artisan storage:link` on your production server to create the symbolic link
  - Ensure `storage/app/public` directory has write permissions:
    ```bash
    chmod -R 775 storage/app/public
    chown -R www-data:www-data storage/app/public  # Adjust user/group as needed
    ```

### 3. Sanctum Stateful Domains
- **Action Required**: Update your production `.env` file with your frontend domain:
  ```env
  SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com,www.your-frontend-domain.com
  APP_URL=https://your-api-domain.com
  ```

## Deployment Checklist

### Before Deployment:
- [ ] Update `.env` file with production values
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure `CORS_ALLOWED_ORIGINS` in `.env`
- [ ] Configure `SANCTUM_STATEFUL_DOMAINS` in `.env`
- [ ] Set correct `APP_URL` in `.env`

### After Deployment:
- [ ] Run `php artisan config:clear`
- [ ] Run `php artisan cache:clear`
- [ ] Run `php artisan storage:link`
- [ ] Set proper permissions on `storage/` and `bootstrap/cache/`:
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```
- [ ] Verify CORS headers are being sent (check browser Network tab)
- [ ] Test POST/PUT/DELETE requests from frontend

## Common Production Issues

### 500 Error on POST/PUT/DELETE but GET works:
1. **CORS Preflight Failure**: Check browser console for CORS errors
   - Solution: Verify `config/cors.php` exists and `CORS_ALLOWED_ORIGINS` is set correctly
   
2. **File Upload Permissions**: Check Laravel logs for permission errors
   - Solution: Run `php artisan storage:link` and set proper permissions
   
3. **Missing Storage Link**: Images/uploads not accessible
   - Solution: Run `php artisan storage:link`

### Authentication Issues:
1. **Token Not Working**: Check `SANCTUM_STATEFUL_DOMAINS` matches your frontend domain
2. **CSRF Token Errors**: Ensure frontend is sending proper headers

## Testing After Deployment

1. Test GET requests (should work):
   ```bash
   curl https://your-api.com/api/products
   ```

2. Test POST request with CORS:
   ```bash
   curl -X POST https://your-api.com/api/products \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Origin: https://your-frontend.com" \
     -d '{"name":"Test","category_id":1}'
   ```

3. Check CORS headers in response:
   - Should see `Access-Control-Allow-Origin` header
   - Should see `Access-Control-Allow-Methods` header

## Server Configuration

### Apache (.htaccess)
The existing `.htaccess` file should handle most cases. Ensure `mod_rewrite` is enabled.

### Nginx
If using Nginx, ensure proper configuration for Laravel:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Notes

- The CORS configuration allows all methods and headers by default
- File uploads now use `storage/app/public` instead of `public/` directory
- Make sure your web server can write to `storage/app/public`
- Old uploaded files in `public/` directory won't be accessible after this change (migrate them if needed)


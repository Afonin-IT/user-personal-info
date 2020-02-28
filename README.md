### Users Personal Info

Для выведения списка пользователей используйте шорткод `[users-list]`.
Шорткод принимает два параметра:
- **count**
	Количество выведенных пользователей  `[users-list count="5"]` 
	(по умолчанию   **3**).
- **slug**
	Ярлык страницы профиля пользователя `[users-list slug="profile"]`
	Пример: `domain.com/profile/?id=1`
	(по умолчанию **user**)

Для вывода профиля пользователя используйте шорткод `[user-profile]` на странице указанной в параметре **slug** шорткода `[users-list]`

### RSA
При активации плагина генерируются приватный и публичный ключи и сохраняются в папку **keys** в директории плагина.
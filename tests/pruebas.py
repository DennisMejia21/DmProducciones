"""Pruebas HTTP. Usar una base vacía y descartable: se crean solicitudes."""
import datetime
import html
import http.cookiejar
import os
import re
import unittest
import urllib.error
import urllib.parse
import urllib.request


BASE = os.environ.get('TEST_URL', 'http://127.0.0.1:8081')


class Navegador:
    def __init__(self):
        self.opener = urllib.request.build_opener(
            urllib.request.ProxyHandler({}),
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),
        )

    def pedir(self, ruta, datos=None):
        datos = urllib.parse.urlencode(datos, doseq=True).encode() if datos is not None else None
        try:
            respuesta = self.opener.open(BASE + ruta, data=datos, timeout=20)
        except urllib.error.HTTPError as error:
            respuesta = error
        return respuesta.status, respuesta.read().decode(), respuesta.url

    def token(self, ruta='/reservas.php'):
        codigo, cuerpo, _ = self.pedir(ruta)
        assert codigo == 200
        return html.unescape(re.search(r'name="csrf" value="([^"]+)"', cuerpo)[1])


class Reservas(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.publico = Navegador()
        cls.admin = Navegador()
        codigo, cuerpo, url = cls.admin.pedir('/login.php', {
            'csrf': cls.admin.token('/login.php'),
            'usuario': os.environ.get('ADMIN_USER', 'admin'),
            'clave': os.environ['ADMIN_PASSWORD'],
        })
        assert codigo == 200 and url.endswith('/admin.php'), 'No se pudo iniciar sesión'
        cls.fecha = (datetime.date.today() + datetime.timedelta(days=2)).isoformat()

    def datos(self, inicio='14:00', fin='16:00'):
        return {
            'csrf': self.publico.token(), 'nombre': 'Cliente <prueba>',
            'apellido': 'Laboratorio', 'telefono': '5491123456789',
            'email': 'prueba@example.com', 'tipo_evento': 'Cumpleaños',
            'fecha_evento': self.fecha, 'hora_inicio': inicio, 'hora_fin': fin,
            'lugar': 'Salón de prueba', 'ciudad': 'Buenos Aires',
            'direccion': 'Calle de prueba 123', 'servicios[]': ['1'],
            'observaciones': 'Solicitud creada por las pruebas automáticas.',
        }

    def crear(self, inicio='14:00', fin='16:00'):
        codigo, cuerpo, _ = self.publico.pedir('/reservas.php', self.datos(inicio, fin))
        self.assertEqual(codigo, 200)
        id_solicitud = re.search(r'Recibimos tu solicitud #(\d+)', cuerpo)
        self.assertIsNotNone(id_solicitud)
        return id_solicitud[1]

    def accion(self, id_solicitud, accion):
        codigo, cuerpo, _ = self.admin.pedir('/admin.php', {
            'csrf': self.admin.token('/admin.php'), 'id': id_solicitud, 'accion': accion,
        })
        self.assertEqual(codigo, 200)
        return cuerpo

    def test_01_acceso_y_csrf(self):
        anonimo = Navegador()
        codigo, _, url = anonimo.pedir('/admin.php')
        self.assertEqual(codigo, 200)
        self.assertTrue(url.endswith('/login.php'))
        self.assertEqual(anonimo.pedir('/reservas.php', {'csrf': 'incorrecto'})[0], 403)
        self.assertEqual(anonimo.pedir('/login.php', {
            'csrf': anonimo.token('/login.php'), 'usuario': 'admin', 'clave': 'incorrecta',
        })[0], 401)
        self.assertEqual(anonimo.pedir('/clima.php?id=1')[2], BASE + '/login.php')
        self.assertEqual(anonimo.pedir('/logout.php')[0], 405)
        self.assertEqual(anonimo.pedir('/database.sql')[0], 404)

    def test_02_validacion(self):
        casos = [
            {'email': 'incorrecto'}, {'telefono': 'abc'}, {'nombre': ''},
            {'hora_fin': '13:00'}, {'hora_inicio': '24:00'},
            {'fecha_evento': '2026-02-30'}, {'fecha_evento': '2000-01-01'},
            {'servicios[]': ['999']}, {'servicios[]': []}, {'observaciones': 'a' * 1001},
        ]
        for cambios in casos:
            with self.subTest(cambios=list(cambios)):
                datos = self.datos()
                datos.update(cambios)
                self.assertEqual(self.publico.pedir('/reservas.php', datos)[0], 422)

    def test_03_reservas_y_estados(self):
        primera = self.crear()
        segunda = self.crear('15:00', '17:00')
        respuesta = self.accion(primera, 'confirmada')
        self.assertIn('quedó confirmada', respuesta)
        self.assertIn('WhatsApp no está configurado', respuesta)
        self.assertIn('Cliente &lt;prueba&gt;', respuesta)
        self.assertNotIn('Cliente <prueba>', respuesta)
        self.assertIn('se superpone', self.accion(segunda, 'confirmada'))
        codigo, cuerpo, _ = self.publico.pedir('/reservas.php', self.datos('15:30', '16:30'))
        self.assertEqual(codigo, 422)
        self.assertIn('ya está reservado', cuerpo)
        # Terminar al comenzar el siguiente evento no constituye superposición.
        contigua = self.crear('16:00', '18:00')
        self.assertIn('quedó confirmada', self.accion(contigua, 'confirmada'))
        self.assertIn('todavía no terminó', self.accion(primera, 'realizada'))
        self.assertIn('quedó cancelada', self.accion(primera, 'cancelada'))
        # Sigue chocando con la contigua hasta que también se cancela.
        self.assertIn('se superpone', self.accion(segunda, 'confirmada'))
        self.accion(contigua, 'cancelada')
        self.assertIn('quedó confirmada', self.accion(segunda, 'confirmada'))
        self.assertIn('acción no es válida', self.accion(segunda, 'rechazada'))
        self.accion(segunda, 'cancelada')
        rechazada = self.crear('10:00', '11:00')
        self.assertIn('quedó rechazada', self.accion(rechazada, 'rechazada'))
        codigo, cuerpo, _ = self.admin.pedir('/admin.php?estado=rechazada')
        self.assertEqual(codigo, 200)
        self.assertIn('solicitud-' + rechazada, cuerpo)
        self.assertNotIn('solicitud-' + segunda + '"', cuerpo)

    def test_04_clima_fuera_de_rango(self):
        datos = self.datos('12:00', '13:00')
        datos['fecha_evento'] = (datetime.date.today() + datetime.timedelta(days=60)).isoformat()
        codigo, cuerpo, _ = self.publico.pedir('/reservas.php', datos)
        self.assertEqual(codigo, 200)
        id_solicitud = re.search(r'Recibimos tu solicitud #(\d+)', cuerpo)[1]
        codigo, cuerpo, _ = self.admin.pedir('/clima.php?id=' + id_solicitud)
        self.assertEqual(codigo, 200)
        self.assertIn('próximos 15 días', cuerpo)
        self.accion(id_solicitud, 'rechazada')

    def test_05_salir(self):
        codigo, _, url = self.admin.pedir('/logout.php', {'csrf': self.admin.token('/admin.php')})
        self.assertEqual(codigo, 200)
        self.assertTrue(url.endswith('/login.php'))
        self.assertTrue(self.admin.pedir('/admin.php')[2].endswith('/login.php'))


if __name__ == '__main__':
    unittest.main(verbosity=2)

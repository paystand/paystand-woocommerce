import static com.kms.katalon.core.checkpoint.CheckpointFactory.findCheckpoint
import static com.kms.katalon.core.testcase.TestCaseFactory.findTestCase
import static com.kms.katalon.core.testdata.TestDataFactory.findTestData
import static com.kms.katalon.core.testobject.ObjectRepository.findTestObject
import com.kms.katalon.core.checkpoint.Checkpoint as Checkpoint
import com.kms.katalon.core.checkpoint.CheckpointFactory as CheckpointFactory
import com.kms.katalon.core.mobile.keyword.MobileBuiltInKeywords as MobileBuiltInKeywords
import com.kms.katalon.core.mobile.keyword.MobileBuiltInKeywords as Mobile
import com.kms.katalon.core.model.FailureHandling as FailureHandling
import com.kms.katalon.core.testcase.TestCase as TestCase
import com.kms.katalon.core.testcase.TestCaseFactory as TestCaseFactory
import com.kms.katalon.core.testdata.TestData as TestData
import com.kms.katalon.core.testdata.TestDataFactory as TestDataFactory
import com.kms.katalon.core.testobject.ObjectRepository as ObjectRepository
import com.kms.katalon.core.testobject.TestObject as TestObject
import com.kms.katalon.core.webservice.keyword.WSBuiltInKeywords as WSBuiltInKeywords
import com.kms.katalon.core.webservice.keyword.WSBuiltInKeywords as WS
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUiBuiltInKeywords
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import internal.GlobalVariable as GlobalVariable
import org.openqa.selenium.Keys as Keys

WebUI.openBrowser('')

WebUI.navigateToUrl(GlobalVariable.domain)

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_username'), GlobalVariable.user)

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_password'), GlobalVariable.password)

WebUI.click(findTestObject('Page_My Account  MyWordpress/input_login'))

WebUI.click(findTestObject('Page_My Account  MyWordpress/a_Payment methods'))

WebUI.click(findTestObject('Page_My Account  MyWordpress/a_Add payment method'))

WebUI.click(findTestObject('Page_My Account  MyWordpress/input_place_order'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_payer_email'), 'avaldez@paystand.com')

WebUI.click(findTestObject('Page_My Account  MyWordpress/button_Card'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_name'), 'ALDO')

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_number'), '4242 4242 4242 4242')

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_expiry'), '12 / 21')

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_security_code'), '123')

WebUI.click(findTestObject('Page_My Account  MyWordpress/button_Enter Billing Informati'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_address_street'), 'street')

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_address_city'), 'guadalajara')

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_address_postal'), '62000')

WebUI.click(findTestObject('Page_My Account  MyWordpress/input_address_state'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_address_state'), 'alabama')

WebUI.click(findTestObject('Page_My Account  MyWordpress/li_Alabama'))

WebUI.click(findTestObject('Page_My Account  MyWordpress/button_Save  Card For Future P'))

WebUI.click(findTestObject('Page_My Account  MyWordpress/a_Logout'))

WebUI.closeBrowser()


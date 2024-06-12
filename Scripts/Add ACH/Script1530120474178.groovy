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

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_username'), GlobalVariable.user)

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_password'), GlobalVariable.password)

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/input_login'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/a_Payment methods'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/a_Add payment method'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/input_place_order'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_payer_email'), 'aldo@paystand.com')

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/button_ACH'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_bank_name_on_account'), 'ALDO')

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/md-select-value_Individual'), FailureHandling.STOP_ON_FAILURE)

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/md-option_Individual'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_bank_routing_number'), '110000000')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_bank_account_number'), '000123456789')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_bank_account_number_repe'), '000123456789')

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/button_Enter Billing Informati'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_address_street'), 'street')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_address_city'), 'guadalajara')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_address_postal'), '62000')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (5)/input_address_state'), 'Alabama')

WebUI.sendKeys(findTestObject('Page_My Account  MyWordpress (5)/input_address_state'), Keys.chord(Keys.ENTER))

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/button_Save  Bank For Future P'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (5)/a_Logout'))

WebUI.closeBrowser()

